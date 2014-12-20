An LTI-compatible launching plugin for Wordpress.


General Wordpress plugin installation instructions can be found here: http://codex.wordpress.org/Managing_Plugins#Automatic_Plugin_Installation


After installing the plugin, add content launching with the [lti-launch]
shortcode.

* Go to the LTI content

![](https://raw.githubusercontent.com/abertranb/wordpress-lti-consumer/master/how_to_install/1.%20Add%20a%20new%20LTI%20Tool.png)

* Set the LTI launch settings

![](https://raw.githubusercontent.com/abertranb/wordpress-lti-consumer/master/how_to_install/2.%20LTI%20launch%20settings.png)

* Copy the short code

![](https://raw.githubusercontent.com/abertranb/wordpress-lti-consumer/master/how_to_install/3.%20Copy%20the%20Shortcode.png)

* LTI embed shortcode in post

![](https://raw.githubusercontent.com/abertranb/wordpress-lti-consumer/master/how_to_install/4.%20LTI%20embed%20shortcode%20in%20post.png)

Some examples:

  [lti-launch resource_link_id=testcourseplacement1]


  [lti-launch consumer_key=yourconsumerkey secret_key=yoursecretkey display=iframe configuration_url=http://launcher.saltbox.com/lms/configuration resource_link_id=testcourseplacement1]


  [lti-launch consumer_key=yourconsumerkey secret_key=yoursecretkey display=newwindow action=link configuration_url=http://launcher.saltbox.com/lms/configuration resource_link_id=testcourseplacement1]


  [lti-launch consumer_key=yourconsumerkey secret_key=yoursecretkey display=self action=button launch_url=http://launcher.saltbox.com/launch resource_link_id=testcourseplacement1]


Options:

- display

  - newwindow: launches into a new window

  - self: launches into the same window, replacing the current content

  - iframe: launches into an iframe embedded in the content

  - modal: launches into an iframe embedded in the content with an input textbox that indicates the height of the iframe ('em').

- action

  - button: shows a button to the user, which they click on to launch

  - link: shows a link to the user, which they click on to launch

- configuration_url: the URL to the launch XML configuration

- launch_url: The launch URL of the LTI-compatible tool



Caution!  Since shortcodes are visible to content viewers if their plugin is
disabled, OAuth secret keys will become visible if this plugin is disabled.
Licensed under the GPLv3. See the LICENSE.md file for details.
