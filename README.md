# Realblog_XH

Realblog_XH facilitates presenting a blog on your CMSimple_XH Website. It
offers the basic blogging features, such as an displaying a chronological
overview of the posts, an optional yearly archive, automatic scheduled
publishing and archiving of posts, searching contents, an RSS feed and very
simplistic categorization. Separately written teasers are supported. Teasers and
articles may contain arbitrary CMSimple_XH scripting. Each blog post can be made
commentable individually, what requires a compatible comments plugin.

For multilingual websites, each language will have its own blog; besides
that, Realblog_XH doesn't have any multilingual capabilities.


- [Requirements](#requirements)
- [Download](#download)
- [Installation](#installation)
- [Settings](#settings)
- [Usage](#usage)
  - [Adminstration](#administration)
  - [Categories](#categories)
  - [Archive](#archive)
  - [RSS Feed](#rss-feed)
  - [Comments](#comments)
- [Backward Compatibility](#backward-compatibility)
- [Troubleshooting](#troubleshooting)
- [License](#license)
- [Credits](#credits)

## Requirements

Realblog_XH is a plugin for [CMSimple_XH](https://cmsimple-xh.org/).
It requires CMSimple_XH ≥ 1.7.0 and PHP ≥ 7.1.0 with the sqlite3 extension.
Realblog_XH also requires [Plib_XH](https://github.com/cmb69/plib_xh) ≥ 1.8
if that is not already installed (see *Settings*→*Info*),
get the [lastest release](https://github.com/cmb69/plib_xh/releases/latest),
and install it.

## Download

The [lastest release](https://github.com/cmb69/realblog_xh/releases/latest)
is available for download on Github.

## Installation

The installation is done as with many other CMSimple_XH plugins. See the
[CMSimple_XH Wiki](https://wiki.cmsimple-xh.org/?for-users/working-with-the-cms/plugins)
for further details.

1. **Backup the data on your server.**
1. Unzip the distribution on your computer.
1. Upload the whole directory `realblog/` to your server
   into the `plugins/` directory of CMSimple_XH.
1. Set write permissions for the subdirectories `css/`, `config/` and
   `languages/`.
1. Navigate `Plugins` → `Realblog` in the back-end
   to check if all requirements are fulfilled.

## Settings

The configuration of the plugin is done as with many other CMSimple_XH plugins
in the back-end of the Website. Navigate to `Plugins` → `Realblog`.

You can change the default settings of Realblog_XH under `Config`.
Hints for the options will be displayed
when hovering over the help icon with your mouse.

Localization is done under `Language`. You can translate the character
strings to your own language (if there is no appropriate language file
available), or customize them according to your needs.

The look of Realblog_XH can be customized under `Stylesheet`.
You can also replace the icons used in the plugin administration;
there are alternative icon sets available in the `images/` folder.

## Usage

To display the blog on a CMSimple_XH page, write:

    {{{Realblog_blog()}}}

To also display the search form, write:

    {{{Realblog_blog(true)}}}

To display the list of the most recent articles on each page,
insert in an appropritate place in the template:

    <?=Realblog_link('%BLOG_URL%')?>

`%BLOG_URL%` has to be replaced by the URL of the main blog page. For details
see the description of the [RSS page setting](#rss-feed).

To also display the teasers of these articles, write:

    <?=Realblog_link('%BLOG_URL%', true)?>

To display the list of the most popular articles on each page, insert in an
appropriate place in the template:

    <?=Realblog_mostPopular('%BLOG_URL%')?>

Regarding `%BLOG_URL%`, see the note above.

### Administration

You can manage the blog posts in the main plugin adminstration. You can
create, edit and delete posts, change their status, etc. The administration is
meant to be pretty much self explaining.

In the `Data Exchange` section you can export the articles to a CSV file,
and also import articles from a CSV file. The CSV file is supposed to be
placed right beside the respective database file and is named
`realblog.csv`. Note that the CSV file is actually tab delimited, but has
not the same format as the old `realblog.txt`, so do not try to import old
data via the CSV import. Note also that importing will overwrite all
existing articles in the database. *It is strongly recommended to make a
backup of the database file before importing from CSV!*

If you want to use the export/import facility to edit the articles offline,
make sure that after exporting and before importing no changes to the
database are made online. Otherwise these changes will be overwritten when
the articles are re-imported. Also do not change any IDs (first column),
because these are used to refer to the page views. It is fine to delete rows,
but if you want to insert rows, do it at the end of the file with
consecutively increasing IDs.

### Categories

Realblog_XH currently has only very basic support for categories. To define
the categories a post belongs to, enter a *comma separated* list of
category names in the respective field of the article form:

    Category 1,Category 2

Note that you can define as many categories as you like.

It is not possible for visitors to filter for categories, but you can prepare
separate CMSimple_XH pages for each category, and display the respective posts
on these pages by passing a second argument to `Realblog_blog()`:

    {{{Realblog_blog(false, 'Category 1')}}}

### Archive

To display the blog archive on a CMSimple_XH page, write:

    {{{Realblog_archive()}}}

To also display the search form, write:

    {{{Realblog_archive(true)}}}

Note that the blog archive must *not* be on the same page as the
actual blog.

### RSS Feed

If enabled in the configuration, Realblog_XH automatically offers an RSS feed
with the published blog posts. Optionally, you can display an RSS feed icon that
links to the feed in the template:

    <?=Realblog_feedLink()?>

You can also pass a single parameter to this function, which specifies the
value of the target attribute of the hyperlink. This can be used to open
the feed in a new window/tab:

    <?=Realblog_feedLink('_blank')?>

Besides some feed related settings in the configuration, there are some
settings in the language file in the section `RSS`. Most important is `page`,
where you have to enter the URL of the page on which the main blog is shown.
It is best to navigate to this page and copy and paste everything after the
question mark up to, but not including, the first ampersand (`&`), if any,
from the address bar of the browser.

### Comments

To add a commenting facility to your blog, you have to install a compatible
comments plugin and enter its name in the configuration of Realblog_XH.

Note for implementers: to be compatible with Realblog_XH you have to write a
class named `%YOURPLUGIN%\RealblogBridge` which implements the interface
`Realblog\CommentsBridge`, which is defined and documented in
`plugins/realblog/classes/CommentsBridge.php`. Make sure this class and its
dependencies are loaded when Realblog_XH needs it; autoloading is recommended.

## Backward Compatibility

Realblog_XH is mostly backward compatible to the original Realblog, so you can reuse
its data files (`realblog.txt`) and use its plugin calls. However, the old plugin
calls are deprecated, and may be removed in the future.

The RSS feed files (`realblog_rss_feed.xml`) are not used anymore; instead
the feeds are built dynamically. You should delete the old files, so that news
readers will not grab the old contents.

## Troubleshooting

Report bugs and ask for support either on
[Github](https://github.com/cmb69/realblog_xh/issues)
or in the [CMSimple_XH Forum](https://cmsimpleforum.com/).

## License

Realblog_XH is free software: you can redistribute it and/or modify it
under the terms of the GNU General Public License as published
by the Free Software Foundation, either version 3 of the License,
or (at your option) any later version.

Realblog_XH is distributed in the hope that it will be useful,
but without any warranty; without even the implied warranty of merchantibility
or fitness for a particular purpose.
See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Realblog_XH. If not, see https://www.gnu.org/licenses/.

© 2006-2010 Jan Kanters  
© 2010-2014 Gert Ebersbach  
© 2014-2023 Christoph M. Becker

Russian translation © 2012 Lybomyr Kydray  
Slovak translation © 2014 Dr. Martin Sereday  
Dutch translation © 2015 Emile Bastings

## Credits

Realblog_XH is a [Fork](https://en.wikipedia.org/wiki/Fork_(software_development))
of Realblog 2.8, which is developed by [Gert Ebersbach](https://www.ge-webdesign.de/).
Realblog (which was formerly called Realblog_XH) is based on
Advancednews 1.0.5 by Jan Kanters. Many thanks to both for making these popular
and useful plugins available under GPL.

Realblog_XH uses jscalendar developed by [Mihai Bazon](http://www.dynarch.com/).
Many thanks to the developer for publishing this component under LGPL.

The plugin icon is designed by [Alessandro Rei](http://www.mentalrey.it/).
Many thanks for publishing the icon under GPL.

The feed icon is designed by [Anomie](https://en.wikipedia.org/wiki/User:Anomie).
Many thanks for releasing it under GPL.

This plugin uses material icons from [Google](https://fonts.google.com/icons?selected=Material+Icons).
Many thanks for making these icons freely available.

Many thanks to the community at the 
[CMSimple_XH Forum](https://www.cmsimpleforum.com/) for tips,
suggestions and testing. Particularly, I want to thank *frase* for many good
suggestions and for pushing the development.

And last but not least many thanks to [Peter Harteg](http://www.harteg.dk/),
the “father” of CMSimple, and all developers of [CMSimple_XH](https://www.cmsimple-xh.org/)
without whom this amazing CMS would not exist.
