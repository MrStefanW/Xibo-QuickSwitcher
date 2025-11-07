# Quick Switcher for Xibo CMS

A small helper for Xibo CMS that provides a quick navigation UI.

![Image](https://i.imgur.com/WrX4JEO.png)



## Important note

- This Quick Switcher has been tested on Xibo version 4.3.1 only. It may work with all Xibo 4 versions, but it may not with any version below that.
- Ensure that you don't accidentally overwrite your own settings-custom.php


## Installation

This project works both on docker and custom Xibo CMS installations.

- Download both folders from this repository.
- Upload the folders and their contents to your server.
- Copy both folders to your Xibo CMS installation directory. (``shared/cms``)
- Ensure www-data has ownership. ``chown -R www-data:www-data /shared/cms``
- Enable the QuickSwitcher in the web interface under: ``Settings -> CMS Theme -> Quick Switcher``

## Usage

1. Open the **Quick Switcher** by pressing `CTRL + K` on your keyboard.
2. Input your desired search result.
3. Use the arrows on your keyboard or cursor to select a result.
4. Press enter or left mouse button.


