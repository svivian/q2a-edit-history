
Edit History plugin for Question2Answer
-------------------------------------------------

This is an event plugin for popular open source Q&A platform, [Question2Answer](http://www.question2answer.org). It stores all edits to posts, allowing users to see what was changed.


Installation & Usage
-------------------------------------------------

1. Download and extract the files to a subfolder such as `edit-history` inside the `qa-plugins` folder of your Q2A installation.

2. Go to Admin > Plugins and click the 'Set up edit history' button.

3. UPDATE: a `noindex` meta tag is included on the revisions pages by default, but you can still block the pages with robots.txt if you prefer. ~~I recommend blocking search engine access to the post revisions, since it could count as duplicate content. Simply add the following two lines to your robots.txt file (where `qa` is your Q2A subfolder if applicable):~~

	User-agent: *
	Disallow: /qa/revisions/


Roadmap
-------------------------------------------------

Here are a few of the planned features for the future:

- Page showing recent edits.
- Allow moderators to roll back revisions, and possibly delete revisions.
- Access control (e.g. only allow moderators/admins to view revisions).


Pay What You Like
-------------------------------------------------

Most of my code is released under the open source GPLv3 license, and provided with a 'Pay What You Like' approach. Feel free to download and modify the plugins/themes to suit your needs, and I hope you value them enough to make a small donation.

### [Donate here](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=4R5SHBNM3UDLU)
