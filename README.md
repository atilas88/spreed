# Nextcloud Talk using Big Blue Button
This work is based on [spreed-bigbluebutton](https://github.com/ramezrafla/spreed-bigbluebutton#nextcloud-talk-using-big-blue-button). This integration allow to load bbb client on an iframe on main window of Talk. In addition it use the [bbb plugin](https://github.com/atilas88/cloud_bbb/tree/cloud_bbb_download_recording_integration) integrated to a recording downloader service.

# Requirements
* This work use the [BigBlueButton plugin](https://apps.nextcloud.com/apps/bbb) for this reason it should be installed and configured
* You should configure **Content-Security-Policy** _frame-src_ and _frame-ancestors_ with url of your bbb server on your web server

# Warning ⚠ 
For security reasons **nextcloud** and **bbb** servers should be on same domain, because most modern browsers apply the same-origin policy. If **nextcloud** and **bbb** servers are in different domains the integration only works for **Mozilla Firefox**.

# Nextcloud Talk-Bbb
![](https://raw.githubusercontent.com/atilas88/spreed/talk-bbb-integration/docs/talk-bbb-integration.png)


## Development Setup

1. 🗂️ Simply clone this repository into the `apps` folder of your Nextcloud development instance.
2. 📦 Run `make dev-setup` to install the dependencies.
3. 🏗️ Run `make build-js`.
4. 🔌 Then activate it through the apps management. 🎉
5. 📘 To build the docs locally, install mkdocs locally: `apt install mkdocs mkdocs-bootstrap`.



