# newsblur2wordpress

NewsBlur saved stories converter to import WordPress

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

I likely will not implement these, because there would be no another needs which I lost my blog articles and try to resume them from NewsBlur.

make things more customizable

  * input and output filenames
  * WordPress blog address to import
  * category name to import

known bugs

  * non-ASCII category/tag names generated invalid slug
