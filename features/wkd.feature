Feature: WKD

  Background:
    Given the database is clean
    And the following Domain exists:
      | name        |
      | example.org |
    And the following User exists:
      | email               | password | wkd_key      |
      | alice@example.org   | asdasd   |              |
    And File "/tmp/invalid.asc" exists with content:
      """
      invalid key
      """
    And File "/tmp/james.asc" exists with content:
      """
      -----BEGIN PGP PUBLIC KEY BLOCK-----

      mQGNBF+B1BUBDADH6aiuRFTgea8JfAc8b9uHmMpnVRGkIXBlakBlSBmoJAxEEAFH
      UU9lalSx4pi0UlUqlVA5+mdHMUv/gQ65EvVyrvUthfrEOnRuGMnotf5qQNL+kSqg
      DScq+yq3jKyAw6Q9ccZcXrq1zyuM0i3YfTb5RiwUrRa9pgh43Bu5j1t4N/ip2zwt
      TUR8orkeffO2qc/Nu3j7XkHZZlGPxa0ZC58N7X/WPySkhM431nZiKJUqD0jBDRSI
      d91dD1jAPt31DsDsme/1CgMbMmOAgsXHFrS+P5oVbWZUwSzcMhPhK0gmUHgT84qD
      BnzL0vudvPYyNMAzgW+zmuYGxggT2fPUiLOYRk/S5jOEWObmlD3zbdNDkNG9Oe6E
      SIUr2n39r2//i+9ImC04xW+7XDMDUA43ip3jtFshpY0wShbIwkzuDZldHi1r38jY
      HAOPxaG/l3J7A1YQlVYfj7/gM9kh0alVTbmS9wplohs5vUXWo+pX1cSgg6EsZWnu
      ViVF/dw024FGQ+EAEQEAAbQZSmFtZXMgPGphbWVzQGV4YW1wbGUub3JnPokB1AQT
      AQoAPhYhBHJxKyknL0gFJC85MGjQX2di3fjWBQJfgdQVAhsDBQkDwmcABQsJCAcC
      BhUKCQgLAgQWAgMBAh4BAheAAAoJEGjQX2di3fjWTtQMAMHDrU/g5tQGzbfc7sax
      ym+gZFqhVgVPUnbbj2G9rcjMjXyoWTZeCZDaxi9NlRy+mia1j0bBCXsocTRZr/qr
      HhHGL8mco/c26O8dVpnBOBeWaytOeQ2KPVlGm9VH4Rn7uUhrvhReeDHEPN2zVptR
      nCD+Kp6yLIBlrHAAXu8fRfURwsLjBCKQT4NYU97pFqGp62lcbCSksPwV+ssM3oHf
      5reL/jrpPS5DurvgOYSj+muKf8UVeI4kIZwJXKWamY+b8tOHSeJdxHkdJiqzicb1
      Uwh0fOiqPC3j+0S43iq+ahSgHn4DqFGT8q+KaF8ApshNU2u8wAoAiWhB+w0Enjsn
      +NbI3g/r+KhxU32/l7i75zZbeI7pe1PIA7OkvZOMCxQXRSKxOXEdgvUbBbMTiQcA
      5dtNGZNJXzHngLFt0y0aGiZ9ABAThSrOWBf9WjSuPHnvqgOxA4h7r+8ZMDvgJ1Hw
      AG45a51Cr19JTROGZlR6VT1KYsdIpk/uM22uDWvh8Unek7kBjQRfgdQVAQwAojmI
      jW0ZquK3zs8s8z9P3TzzMvKRKtvlOFzcujOGoOoSgCGY8y9qJoPem0y6G+foEE8C
      EwzAXVsKA+F9TsJj0rjj9qzOolxMTL6sBU/k4fqyOmLiLFGZBeYJxSsrzE0+CTm1
      NDe8JkchvL1CMBdudk6rK5Oz52apSDjxNsAIp2QeYtRyziyuPYSsZVwQby5FEV58
      EuzQ2C2bKSoYCLTcVA44eghlAWN1OjvMhOJCEq5U9Z7fWCBOa8OTXHEbTX+m9FXh
      dLnVq7yISxlw+mvVf0xd6qYp3g7cOgH9dwe6O+yOpo5+k12WkDb7sImgn7WtWIH4
      UCniiXbVKnfXlkMgs4KrKg74iTSFGGCKCv1qFh5DwUf5Q2aSQQ7QLwit8F8Uj9Mm
      XwO9ks/HytJ4pb7eX3QGktwn51EQeucWVEx1nSUV5Y1NS45mQE97P5syYtF3K1s6
      F8D5HaAqmuShAHbAuytxG+8lpxni2eyZDrfHaPNB9e7WVuUw6dLuZmAhHwRtABEB
      AAGJAbwEGAEKACYWIQRycSspJy9IBSQvOTBo0F9nYt341gUCX4HUFQIbDAUJA8Jn
      AAAKCRBo0F9nYt341lGjC/9+0xKSlack9aDn4234fJhhRXu4D1dA9dKhQT5m5UUi
      9RGHcFQ4gGtGxyC6MJ7+B9jlb7ywsGZTRiiLvBjlv7XfKUqP0UAMR4bsVuw5ZZx/
      q5PUku11ME18OdvZGbzg2WOAEqSeELW4FkTne38GXwnPkM6/DYe8JkPY5KSCoccW
      z5yPN631UagLRzyVOsJokyMhjHW6oWgtuwy9NhxMcNPliCURjKQg3txpdEKE69fQ
      qkCWSAppmDO+YMnNp5ufQB/nQrW/pIAWU6FgJoMPuoYZ5TDHOMTm8EOxj8oveMBN
      l8Kh4EH9zP5lJkGYzck+hZfjrBxCrMW7s8KueItcwx4LV619yATVMiMbQ8yUP8XS
      XhO9u0FBGcEwAb8vj4tXff233xxHypcqQ8Ki3txpv1oQnO/2ZSEXjgIkycrICjDQ
      9/PnMko/27Hwte7wTPWw2eOlMljYlAfmwrLu8a0C9fCGJ/BED2/TfV0VD4qi9tMM
      hx77izIzoqOrwcQ7yTyR+Uo=
      =hivm
      -----END PGP PUBLIC KEY BLOCK-----
      """
    And File "/tmp/alice_two_keys.asc" exists with content:
      """
      -----BEGIN PGP PUBLIC KEY BLOCK-----

      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
      =z1eK
      -----END PGP PUBLIC KEY BLOCK-----
      """
    And File "/tmp/alice1.asc" exists with content:
      """
      -----BEGIN PGP PUBLIC KEY BLOCK-----

      mQGNBF+B09wBDACe08x3/cZYBdYfKm062Bj9DtSkq9K7uZSif0alSm1x10hcNh3d
      31EjIBLPt7PNowYiADj2aLFscC3UjO/nNKqE6wXXPB5yfeW0ES9NxgElDgyHUvim
      q1H+L2ji+QHrsZwgSVD1NGi/2yVfTuWWjKkcUYjxLFKdLpjfy0I92IagSsPOzGdL
      HxzwuXvWP/D6FLWDw3n6bddWvysZzRX8PIuICJJ/VZ4lUbfXpzKyMD9hc5Uqpi+a
      b++1I4wYhy5H5Kll+iBa7vfRAPjKhml9A+SFPfg4tgv+C5izLwGi/1SYBfVMTmwT
      ly42pMyjjGbnWZ4GW7sGbCHlgIpL1zFfoUdXeBZJrG9W4ReoD42LZUZkn+lzSHiv
      62tjH1Zh+oVlf2sWmCGuFa3WL95mOmUSyY+ne1w8ZlEB2nVq6LU09XxaztYTC65H
      GS7lZ5MGXsfcWyugBi0uuS01DGHPBZA5Gj/pqAHzoLYo0pEaEWvkKHYOI2bhHd4V
      ikIW6KbJ1cEgc6kAEQEAAbQZQWxpY2UgPGFsaWNlQGV4YW1wbGUub3JnPokB1AQT
      AQoAPhYhBHMBJUfCXeKg0JeMRq2NUs0igf7CBQJfgdPcAhsDBQkDwmcABQsJCAcC
      BhUKCQgLAgQWAgMBAh4BAheAAAoJEK2NUs0igf7CLJoL/2jBag9rkhNAC3omHvt4
      W8qO6Yx5pmLtes6ABksmXNZ3v9/oGYG6t2nBasfiMOBO806jA7F8HRDTn0Acp2x0
      qPamsTGWRfFjL9zK4l67ZsPJO1nWN5v2iqF9015TqLosZP02rrT+nbtwZTSNmqrc
      gEKgl1K3vC1bhwi3a8uAqBr+LbxzpM2/op+Iccus5fAv1L2xlcpQYGjfeQ4Wcl2D
      BIagLFFJEZeZosMRBD4ljibAIt2xzlPkth4abW0eHcHXfg6cuwZqqRwGC52OnEEH
      w04T38Uy8Jqgz+4aZYzMUub1hkLAI3CYC9XwKvNM9I0b2M4fwhKjlZxoJXInbu/a
      NDXKD/fU2tULxObhWfbGN588vGy9VzHL/9Ph7bGPJ4+W0pkyU41pLS8ZA3LtQB40
      z9lEwd2Bop63abxgObRytIcClbTg/YtVngaaEtuv6tkxVuN7eHX+l6d2buTO3+0j
      c2XINitqDSHzUlHF8mtpyARH70X3tKGkZxnnml1yhBvBGrkBjQRfgdPcAQwA6TBo
      lO+tbbfGKTH6IikJwA9wYK0W4cK7dXKfwnQznYd2YZ6xnZTQOdMbMnmhjWjsfZ0d
      dPUttSuavUUCpM7ZF2UpmJQJMNBVJXfgzz+YqlnOcWTp72ZRvOJLOo0cQYFT7g54
      Ff/R98W0jsz28mi9fZDG6i11SkHJw9H7VZzJ5WwJXsmMdAhcxVb342hUstwL3vse
      MT+Ni7G+aF/r3gkkmSW2Uo0cG37DCbDuGQGE/F1OCzjxRvCI2hFhAjbxDz1PDLBA
      flHJFHAcTvyBNURayjKTQvx04Rwk4/JEJzX3ll5+uYgD7WdyoL939U+LyTTzv8gS
      5TDkaUroMy14VAP+hptvdAtYB8X+FCQPTNQqaHc8mGsH04GIju7hXibJ92lPhb/z
      8xVDgw15Sqb7cdCPDf+9nPtnZ+mGSJzsaNYcPV1J9WJCfz6jnVOsuxxUh88R4c+r
      2W/aWKlqqt5DIdcE5BmJTywCX8Ae5IgjgAckh7/6h66XovwpG/ruKruWZqixABEB
      AAGJAbwEGAEKACYWIQRzASVHwl3ioNCXjEatjVLNIoH+wgUCX4HT3AIbDAUJA8Jn
      AAAKCRCtjVLNIoH+wq9SC/4t41rMGUWet8XrO53bqgxZVyvEznfwfIDs1F/I8OdO
      UaLN4h8s7xbmgR0TBLFcgavkx6xdQrFHQzNJwW7N99J3GK/Ue03doBhT0l6NgG7z
      zNrSVeLo/X/uvjHxXYFli6vC13UfOtFSAcfA5v5+zmQ22FlwFAdtLvoQhKdVlTWN
      5bGqJ2m1MQH+qAtAnxbpeSjlN3jUUVQbaY2nl0HAvJ/ex+KbjCkQ39sIEQ32GVM5
      ndDhaV2vyjGFpi7mdUUFmvmeLhdca23hHAwjUyQTq2eSZ1QvJQpy+jkMwXNqbUcC
      ONL3+LiGN6rxLD/9xoHdzevYf4LoNu5OtFnEbmGwRS8aN910SwE895epTzFQ0LUl
      qk1v60mCjI2igAetGiK2Z764FSZZe1L+adLH5R+Z2nGKTvTjuCB4tveNDkf1f4zs
      PQL+FP9xT4mjoy003maO5Ccoo8ggGlUsqCV6TcqeW7tYU9BTegzasSrNiI5y/bUp
      hMNhWBRccEo8lQr8xtvkrfY=
      =K+Hi
      -----END PGP PUBLIC KEY BLOCK-----
      """
    And File "/tmp/alice2.asc" exists with content:
      """
      -----BEGIN PGP PUBLIC KEY BLOCK-----

      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
      KUjBeu0wGNW/eqfHaBWlkNI=
      =F2SF
      -----END PGP PUBLIC KEY BLOCK-----

      """

  @upload-wkd-both-empty
  Scenario: Upload empty WKD key
    When I am authenticated as "alice@example.org"
    And I am on "/wkd"
    And I fill in the following:
      | upload_wkd_key_keyFile       |              |
      | upload_wkd_key_keyText       |              |
    And I press "Publish OpenPGP key"

    Then I should be on "/en/wkd"
    And I should see text matching "Please upload key either as file or as ASCII text."
    And the response status code should be 200

  @upload-wkd-both
  Scenario: Upload both WKD keys
    When I am authenticated as "alice@example.org"
    And I am on "/wkd"
    And I fill in the following:
      | upload_wkd_key_keyFile       | /tmp/invalid.asc |
      | upload_wkd_key_keyText       | text             |
    And I press "Publish OpenPGP key"

    Then I should be on "/en/wkd"
    And I should see text matching "Please upload key either as file or as ASCII text."
    And the response status code should be 200

  @upload-wkd-file-invalid
  Scenario: Upload invalid file
    When I am authenticated as "alice@example.org"
    And I am on "/wkd"
    And I fill in the following:
      | upload_wkd_key_keyFile       | /tmp/invalid.asc |
      | upload_wkd_key_keyText       |                  |
    And I press "Publish OpenPGP key"

    Then I should be on "/en/wkd"
    And I should see text matching "The uploaded file doesn't contain OpenPGP data."
    And the response status code should be 200

  @upload-wkd-text-invalid
  Scenario: Upload invalid text
    When I am authenticated as "alice@example.org"
    And I am on "/wkd"
    And I fill in the following:
      | upload_wkd_key_keyFile       |              |
      | upload_wkd_key_keyText       | text         |
    And I press "Publish OpenPGP key"

    Then I should be on "/en/wkd"
    And I should see text matching "The uploaded file doesn't contain OpenPGP data."
    And the response status code should be 200

  @upload-wkd-file-wrong-key
  Scenario: Upload file with wrong key
    When I am authenticated as "alice@example.org"
    And I am on "/wkd"
    And I fill in the following:
      | upload_wkd_key_keyFile       | /tmp/james.asc  |
      | upload_wkd_key_keyText       |                 |
    And I press "Publish OpenPGP key"

    Then I should be on "/en/wkd"
    And I should see text matching " No keys for your mail address found in uploaded data."
    And the response status code should be 200

  @upload-wkd-file-two-valid-keys
  Scenario: Upload file with two matching keys
    When I am authenticated as "alice@example.org"
    And I am on "/wkd"
    And I fill in the following:
      | upload_wkd_key_keyFile       | /tmp/alice_two_keys.asc  |
      | upload_wkd_key_keyText       |                          |
    And I press "Publish OpenPGP key"

    Then I should be on "/en/wkd"
    And I should see text matching "More than one key for your mail address found in uploaded data. Please upload only one key."
    And the response status code should be 200

  @upload-wkd-file-valid-key
  Scenario: Upload file with matching key
    When I am authenticated as "alice@example.org"
    And I am on "/wkd"
    And I fill in the following:
      | upload_wkd_key_keyFile       | /tmp/alice1.asc  |
      | upload_wkd_key_keyText       |                  |
    And I press "Publish OpenPGP key"

    Then I should be on "/en/wkd"
    And I should see text matching "Your OpenPGP key got uploaded."
    And I should see text matching "Fingerprint: 7301 2547 C25D E2A0 D097 8C46 AD8D 52CD 2281 FEC2"
    And File "/tmp/.well-known/openpgpkey/example.org/hu/kei1q4tipxxu1yj79k9kfukdhfy631xe" should exist
    And the response status code should be 200

  @upload-wkd-file-replace-valid-key
  Scenario: Upload file with another matching key
    When I am authenticated as "alice@example.org"
    And I am on "/wkd"
    And I fill in the following:
      | upload_wkd_key_keyFile       | /tmp/alice2.asc  |
      | upload_wkd_key_keyText       |                  |
    And I press "Publish OpenPGP key"

    Then I should be on "/en/wkd"
    And I should see text matching "Your OpenPGP key got uploaded."
    And I should see text matching "Fingerprint: 4964 21CF 18B5 0D8D 61DB 26BA D3CB BC7A 09CC 0EDF"
    And File "/tmp/.well-known/openpgpkey/example.org/hu/kei1q4tipxxu1yj79k9kfukdhfy631xe" should exist
    And the response status code should be 200

  @upload-wkd-delete-key
  Scenario: Upload and delete key
    When I am authenticated as "alice@example.org"
    And I am on "/wkd"
    And I fill in the following:
      | upload_wkd_key_keyFile       | /tmp/alice2.asc  |
      | upload_wkd_key_keyText       |                  |
    And I press "Publish OpenPGP key"
    And I am on "/wkd/delete"
    And I fill in the following:
      | delete_wkd_password  | asdasd  |
    And I press "Delete OpenPGP key"

    Then I should be on "/en/wkd"
    And I should see text matching "Your OpenPGP key was deleted."
    And I should not see text matching "Fingerprint:"
    And File "/tmp/.well-known/openpgpkey/example.org/hu/kei1q4tipxxu1yj79k9kfukdhfy631xe" should not exist
    And the response status code should be 200
