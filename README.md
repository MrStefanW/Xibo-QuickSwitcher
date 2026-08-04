# Quick Switcher for Xibo CMS (proof-of-concept)

A small proof-of-concept helper for Xibo CMS that provides a quick navigation UI.

**Note:** A pull request has been created to integrate this functionality directly into Xibo CMS ([#3170](https://github.com/xibosignage/xibo-cms/pull/3170)). This repository is intended only as a standalone prototype/demo.

![Image](https://i.imgur.com/WrX4JEO.png)



## Important note

- This no longer works since version 4.5.0, as Xibo has changed how custom themes work.
- This Quick Switcher has been tested with all versions between Xibo 4.3.1 and 4.4.5 
- Ensure that you don't accidentally overwrite your own settings-custom.php
- At this moment, it is advised to have "All folders" checked, unless everything is within a single folder.


## Installation

This project works both on docker and custom Xibo CMS installations.

- Download both folders from this repository.
- Upload the folders and their contents to your server.
- Copy both folders to your Xibo CMS installation directory. (``shared/cms``)
- Ensure www-data has ownership. ``chown -R www-data:www-data /shared/cms``
- Enable the QuickSwitcher in the web interface under: ``Settings -> CMS Theme -> Quick Switcher``
- Reload the page.

## Usage

1. Open the **Quick Switcher** by pressing `CTRL + K` on your keyboard.
2. Input your desired search result.
3. Use the arrows on your keyboard or cursor to select a result.
4. Press enter or left mouse button.


