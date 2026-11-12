FROM quay.io/keycloak/keycloak:26.7

# Userli user storage provider, latest release. To test a local build, mount the
# jar over /opt/keycloak/providers/userli.jar (see docker-compose.override.yml).
ADD --chown=keycloak:keycloak \
    https://github.com/systemli/userli-keycloak-provider/releases/latest/download/userli-storage-provider-1.0.0.jar \
    /opt/keycloak/providers/userli.jar
