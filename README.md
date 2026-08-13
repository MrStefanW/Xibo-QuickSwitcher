# Quick Switcher for Xibo CMS

A small helper for Xibo CMS that provides a quick navigation UI.

![Image](https://i.imgur.com/WrX4JEO.png)



## Important note

- This version is for Xibo 4.5.0 and beyond. For older Xibo 4 installations, check the [releases](https://github.com/MrStefanW/Xibo-QuickSwitcher/releases/tag/Release).
- Ensure that you don't accidentally overwrite your own settings-custom.php, only append the contents to your file.


## Installation

This project works both on docker and custom Xibo CMS installations.

- Download both folders from this repository.
- Upload the folders and their contents to your server.
- Append the code from settings-custom.php to your settings-custom. Don't overwrite it!
- Copy all files to your Xibo CMS installation directory. (``shared/cms/custom``)
- Ensure www-data has ownership. ``chown -R www-data:www-data /shared/cms``
- Give the page a hard refresh.

## Usage

1. Open the **Quick Switcher** by pressing `CTRL + K` on your keyboard.
2. Input your desired search result.
3. Use the arrows on your keyboard or cursor to select a result.
4. Press enter or left mouse button.


