# Bloembraaden
We've got your backend

## We like unicorns more than uniforms
Bloembraaden is a cms for digital agencies and designers with a focus on freedom.
You can design anything you want, anyway you like. Bloembraaden handles the boring stuff.

## How to
This is a self contained cms to host simple websites designed by hand, written in html, js and css.

Included is an example website that will run on the default url.

You can simply point domains and / or subdomains at your server running Bloembraaden, and Bloembraaden will pick this up and serve the website.

Each website ‘instance’ has a client part and a server part.
On the client part (htdocs/instance/name_of_the_instance) you set your css, custom js and public files (images) used in your design.
On the server part (core/presentation/instance/name_of_the_instance) you can currently configure 2 files:
- editor_config.json, the configuration of the editor for this instance.
- name_of_the_instance.mo (and .po if you wish), the translation file for all generic texts. Use PoEdit to generate it from the code.

## Config
The project contains an example config. Rename that to config.json and fill it in with your own values.
Make sure the folders mentioned in the config are writable by the user your webserver is running as.

For your emailprovider you can choose between mailchimp, mailgun and sendgrid. You may need to tweak the code because currently only Mailchimp is used.
