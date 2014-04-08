
Edit History plugin for Question2Answer
=================================================

This is an event plugin for [Question2Answer](http://www.question2answer.org). It stores all edits to posts, allowing users/admins to see what was changed.


Installation & Usage
-------------------------------------------------

1. Download and extract the files to a subfolder such as `edit-history` inside the `qa-plugins` folder of your Q2A installation.

2. Go to Admin > Plugins and click the link to set up the database.

3. Under the plugin options, tick the first checkbox to start tracking edits and save. Options for the user level allowed to view edits and time for which two edits are counted as separate (aka 'ninja edit time') can be set here.

4. Search engine access to the revisions pages is blocked via a `noindex` meta tag (to prevent duplicate content issues). Optionally, the pages can be blocked using robots.txt as well. Add the following two lines (where `qa` is your Q2A subfolder if applicable):

		User-agent: *
		Disallow: /qa/revisions/


Version history
-------------------------------------------------

### 1.4:

- Allow reverting of edits to earlier revision and option to control permission level (default Admin).
- Hide content if only title changed.
- Newly designed revision page, CSS moved to separate file.

Note: this version requires a database upgrade.

### 1.3:

- Single Sign-On support.
- Link username in revisions.
- Fix 'back to post' link.
- Fix incorrect permissions.
- Refactor SQL to separate functions.

### 1.2:

- Option to control 'ninja' edit time.
- Fix revision links for any Q2A URL structure.
- Fix diff_string static warning.
- Refactor HTML output to separate function.

### 1.1:

- Option to restrict revision viewing to any user group (e.g. Registered Users, Moderators, Admins).
- Add plugin update check

### Roadmap (possible future features)

- Page showing recent edits.
- Allow moderators to roll back revisions, and possibly delete revisions.


Pay What You Like
-------------------------------------------------

Most of my code is released under the open source GPLv3 license, and provided with a 'Pay What You Like' approach. Feel free to download and modify the plugins/themes to suit your needs, and I hope you value them enough to make a small donation.

### [Donate here](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4R5SHBNM3UDLU)
