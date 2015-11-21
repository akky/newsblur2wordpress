# newsblur2wordpress

This is a tool to convert the saved NewsBlur stories to WordPress import format.

It is NOT affiliated with NewsBlur nor WordPress. Not at all.

This script is very crude, only satisfied my own one-time requirement. Some codes were copy-and-pasted web q-and-a pages, not really cleaned out.

I could just throw away this script, but think that there would be someone in future who fall into this rare situation between NewsBlur and WordPress.

## Preparation

You first export items from NewsBlur by jmorahan's newsblur-export.
https://github.com/jmorahan/newsblur-export

  * star items you need to export
  * configure user/password
  * run the script
  * you will get an exported file "starred_stories.json" in current directory.

## Usage

$ git clone https://github.com/akky/newsblur2wordpress.git

$ cd newsblur2wordpress

$ composer install

$ php newsblur2wordpress

which read the exported file "starred_stories.json" in current directory, generate WordPress import file (WXR) named "newsblur-exported.xml".

## ToDo

As I had already solved my own problem, probably no future updates on this code. Send PR if you fix something. The followings may be good to have.

  * input and output filenames from parameters
  * WordPress blog address to import
  * category name to import

known bugs

  * non-ASCII category/tag names generated incompatible slug from the official WordPress (If you do not like the URLs changed by this, you may need to copy WP's slug function into this script.)
