In order to provide a faster style coding I migrated the existing styles from less to stylus (We still get the final css file).

It will be easier to me to code in stylus than in less (Im more familiar with stylus syntax and the code will be cleaner).

Folders:
	bootstrap - Contains all the resources from bootsrap and some dependencies (Such as JQuery and a test javascript file), also it contains all the icons in both a template and a raw format.

	html - Contains the templates used to test the existing styles

	styl - Contains the non compiled stylus files

	fulleron-theme.css - The compiled file (The most updated one)

	fulleron-theme.styl - The master file (The one that you compile)

	startWatchMode.bat - A shortcut to start watch mode on fulleron-theme.styl (This also compiles on save replacing the fulleron-theme.css file so use with caution)

	dashboard - Contains the less version of the timeline

*Note: In order to compile the stylus code you need to install stylus (npm install -g stylus)