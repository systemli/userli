local json = require("json")
local math = require("math")

-- env vars
local env_userli_token         = os.getenv("USERLI_API_ACCESS_TOKEN")
local env_userli_host          = os.getenv("USERLI_HOST")
local env_dovecot_debug        = os.getenv("DOVECOT_LUA_DEBUG") or ""
local env_dovecot_agent        = os.getenv("DOVECOT_LUA_AGENT") or "Userli-Dovecot-Adapter"
local env_dovecot_max_attempts = os.getenv("DOVECOT_LUA_MAX_ATTEMPTS") or "3"
local env_dovecot_timeout      = os.getenv("DOVECOT_LUA_TIMEOUT") or "10000"
local env_dovecot_insecure     = os.getenv("DOVECOT_LUA_INSECURE") or "false"

-- log messages
local log_msg = {}
log_msg["env_userli_host"]   = "Environment variable USERLI_HOST must not be empty"
log_msg["env_userli_token"]  = "Environment variable USERLI_API_ACCESS_TOKEN must not be empty"
log_msg["userli-error"]      = "Could not connect to Userli API. "
log_msg["http-ok"]           = "Lookup successful"
log_msg["http-ok-malformed"] = "Lookup failed: HTTP-status is 200, but HTTP-response is malformed."
log_msg["http-failed"]       = "Lookup failed: HTTP-status "
log_msg["http-unexpected"]   = "Lookup failed: Unexpected HTTP-status: "


local protocol = "https"
if string.lower(env_dovecot_insecure) == "true" then
    protocol = "http"
end
local api_path = "/api/dovecot"
local api_url = protocol .. "://" .. env_userli_host .. api_path

local http_client = dovecot.http.client {
    timeout      = math.tointeger(env_dovecot_timeout);
    max_attempts = math.tointeger(env_dovecot_max_attempts);
    debug        = string.lower(env_dovecot_debug) == "true";
    user_agent   = env_dovecot_agent
}

function script_init()
    if not env_userli_token then
        dovecot.i_error(log_msg["env_userli_token"])
        return 1
    end
    if not env_userli_host then
        dovecot.i_error(log_msg["env_userli_host"])
        return 1
    end

    -- Only added in dovecot 2.4.0
    -- dns = dns_client:lookup(userli_host, auth)
    -- if not dns then
    --     dovecot.i_error("Cannot resolve userli hostname: " .. env_userli_host)
    --     return 1
    -- end

    -- test if userli api is available
    local http_request = http_client:request {
        url = api_url .. "/" .. "status";
        method = "GET";
    }
    http_request:add_header("Content-Type","application/json")
    http_request:add_header("Accept","application/json")
    http_request:add_header("Authorization","Bearer " .. env_userli_token)
    local http_response = http_request:submit()
    if http_response:status() == 200 then
        return 0
    else
        dovecot.i_error(log_msg["userli-error"] .. "HTTP-Status: " .. http_response:status() .. "; Reason: " .. http_response:reason())
        return 1
    end
end

function script_deinit()
    return 0
end

function auth_userdb_lookup(request)
    local http_request = http_client:request {
        url    = api_url .. "/" .. request.original_user;
        method = "GET";
    }
    http_request:add_header("Content-Type","application/json")
    http_request:add_header("Accept","application/json")
    http_request:add_header("Authorization","Bearer " .. env_userli_token)
    local http_response = http_request:submit()

    if http_response:status() == 200 then
        local success, data = pcall(json.decode, http_response:payload())
        if not success then
            request:log_error(log_msg['http-ok-malformed'])
            return dovecot.auth.USERDB_RESULT_INTERNAL_FAILURE, ""
        end

        if not(data and data.body and data.body.user and data.body.quota and data.body.mailCrypt and data.body.mailCryptPublicKey) then
            request:log_error(log_msg['http-ok-malformed'])
            return dovecot.auth.USERDB_RESULT_INTERNAL_FAILURE, ""
        end

        local attributes = {}
        attributes["user"] = data.body.user

        if data.body.quota ~= "" then
            attributes["quota_rule"] = data.body.quota
        end
        -- Only return mailcrypt attributes if mailcrypt is enabled for user:
        if data.body.mailCrypt == 2 then
            attributes["mail_crypt_global_public_key"] = data.body.mailCryptPublicKey
            attributes["mail_crypt_save_version"]      = data.body.mailCrypt
        end
        request:log_info(log_msg['http-ok'] .. http_response:status())
        return dovecot.auth.USERDB_RESULT_OK, attributes
    end

    if http_response:status() == 404 then
        request:log_warning(log_msg['http-failed'] .. http_response:status())
        return dovecot.auth.USERDB_RESULT_USER_UNKNOWN, ""
    end

    request:log_error(log_msg['http-unexpected'].. http_response:status())
    return dovecot.auth.USERDB_RESULT_INTERNAL_FAILURE, ""
end

function auth_password_verify(request, password)
    local http_request = http_client:request {
        url    = api_url .. "/" .. request.original_user;
        method = "POST"
    }
    http_request:add_header("Content-Type","application/json")
    http_request:add_header("Accept","application/json")
    http_request:add_header("Authorization","Bearer " .. env_userli_token)
    http_request:set_payload(json.encode({password = password}))
    local http_response = http_request:submit()

    if http_response:status() == 200 then
        local success, data = pcall(json.decode, http_response:payload())
        if not success then
            request:log_error(log_msg['http-ok-malformed'])
            return dovecot.auth.PASSDB_RESULT_INTERNAL_FAILURE, ""
        end

        -- mailCryptPrivateKey may be empty, but cannot be nil
        if not(data and data.body and data.body.mailCrypt and data.body.mailCryptPrivateKey and data.body.mailCryptPublicKey) then
            request:log_error(log_msg['http-ok-malformed'])
            return dovecot.auth.PASSDB_RESULT_INTERNAL_FAILURE, ""
        end

        local attributes = {}
        -- Only return mailcrypt attributes if mailcrypt is enabled for user:
        if data.body.mailCrypt == 2 then
            attributes["userdb_mail_crypt_save_version"]       = data.body.mailCrypt
            attributes["userdb_mail_crypt_global_private_key"] = data.body.mailCryptPrivateKey
            attributes["userdb_mail_crypt_global_public_key"]  = data.body.mailCryptPublicKey
        end
        return dovecot.auth.PASSDB_RESULT_OK, attributes
    end

    if http_response:status() == 401 then
        request:log_warning(log_msg['http-failed'] .. http_response:status())
        return dovecot.auth.PASSDB_RESULT_PASSWORD_MISMATCH, ""
    end

    if http_response:status() == 403 then
        local data = json.decode(http_response:payload())
        local message = data['message'] or "unknown http forbidden error"

        request:log_warning("Lookup failed: " .. message)

        if message == "user disabled due to spam role" then
            return dovecot.auth.PASSDB_RESULT_USER_DISABLED, ""
        end

        if message == "user password change required" then
            return dovecot.auth.PASSDB_RESULT_PASS_EXPIRED, ""
        end

        return dovecot.auth.PASSDB_RESULT_INTERNAL_FAILURE
    end

    if http_response:status() == 404 then
        request:log_warning(log_msg['http-failed'] .. http_response:status())
        return dovecot.auth.PASSDB_RESULT_USER_UNKNOWN, ""
    end

    if http_response:status() == 500 then
        local msg =  log_msg['http-failed'] .. http_response:status()
        local data = json.decode(http_response:payload())
        local err = data['error']
        if err then
            msg = msg .. ", Upstream-error: " .. err
        end
        request:log_error(msg)
        return dovecot.auth.PASSDB_RESULT_INTERNAL_FAILURE, ""
    end

    request:log_error(log_msg['http-unexpected'] .. http_response:status())
    return dovecot.auth.PASSDB_RESULT_INTERNAL_FAILURE, ""
end

