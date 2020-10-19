+++
title = "Web Key Directory"
description = ""
weight = 5
+++

Userli brings support for [OpenPGP Web Key
Directory](https://gnupg.org/faq/wkd.html), a OpenPGP key discovery system.
Users can import and update their OpenPGP key and it will be published in the
Web Key Directory according to the [OpenPGP Web Key Directory Internet
Draft](https://datatracker.ietf.org/doc/draft-koch-openpgp-webkey-service).

The WKD feature depends on [GnuPG](https://gnupg.org/) being installed.

The WKD directory path can be configured by setting `WKD_DIRECTORY` in the
dotenv (`.env`) file. Write access to the WKD directory is required.

The WKD directory format can be configured by setting `WKD_FORMAT` in the
dotenv (`.env`) file. The supported settings are `advanced` (default) and
`direct`. See the [OpenPGP Web Key Directory Internet
Draft](https://datatracker.ietf.org/doc/draft-koch-openpgp-webkey-service)
for details.

The WKD directory can be regenerated at any time by running the console
command: 

    bin/console app:wkd:export-keys

{{%children style="h2" description="true"%}}
