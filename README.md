CS-Project
==========

PostgreSQL + PHP driven project management website. All projects can have notes, todo's, and associated helpdesk issues. Supports separation of projects into teams/groups. Extensive logging system and unique upgrade system for ease of upgrading.


CS-Project Feature Overview
--------

*Simplicity*
	This system is built on a very simple premise: Project managers manage projects.  There are various types of issues associated with those projects (bugs, feature requests, etc).  Each project and issue have tasks associated with them.  Everyone is interested in how long things take.

	CS-Project incorporates all of these principles.  Projects are a broad indicator of the various pieces of a system.  Large projects can be broken into smaller parts (sometimes referred to as “milestones”) to help focus efforts.  New issues, including bugs and feature requests, can be added to a project to help maintain a list of all the things that are requested or have been found to be problematic.  Tasks create a breakdown of things that need to be accomplished.

	To further simplify things, CS-Project doesn't tie its users down to a specific way to use it: it can be easily used strictly as a Trouble Ticket (Help Desk) system, or just projects, or both.  

*Easy Setup*
	CS-Project is very easy to setup.  Once the webserver accepts connections to the website name of your choosing (like "http://project.crazedsanity.com"), just go to it's address, and the setup program will start immediately. Each step explains exactly what will happen, provides defaults for nearly every item, and shows the output from every completed step.  No need to run any scripts on a command line, loading things manually, or anything else: it's all done for you.

	Normal installations of specialized websites that are downloaded off the 'net require steps similar to the following:
		1.) Download
		2.) extract tarball/zip file
		3.) edit source file/config files by hand (relying on the developer's notes to guide)
		4.) tweak & load database schema by hand
		5.) open website in browser
		6.) go back to step #3, rinse, and repeat until it seems to work
	Setting up CS-Project:
		1.) svn checkout https://cs-project.svn.sourceforge.net/svnroot/cs-project/releases/1.0
		2.) open website in browser
		3.) follow step-by-step instructions until install is complete

*Upgradeability*
	CS-Project was built with the understanding that no piece of software is perfect.  New features are implemented.  Bugs are fixed.  Backend systems are tweaked.  Security issues are patched.

	When these upgrades are available, especially for critical bugs, users want—and need—to get them as quickly and easily as possible.  And even in the case of a severe bug that causes data corruption, scripted upgrades can usually fix this automatically with little or no interuption (see "Scripted Upgrades").

	Have you ever downloaded & setup a piece of software, then found out months later that upgrades involved completely destroying the installation & re-extracting a new copy?  Or even creating a new database, with the understanding that the existing data has to be converted into the new schema by hand?  Yes, it happens, and it's very rare for the layout of the database to be documented, let alone documented *well*.

	ENTER SUBVERSION, STAGE RIGHT.  To overcome this unfriendly obstacle, CS-Project was also setup to allow for even easier setup via a Subversion checkout (see http://subversion.tigris.org). To illustrate the ease of updating, let's compare updating a traditional web application with CS-Project.  

	TRADITIONAL:
		1.) download latest update
		2.) extracting tarball/zip file:
			a.) extract over the top of existing install
				i.) hope you spot all files that were deleted
				ii.) fiddle with changed/renamed config files
			b.) extract into new folder
				i.) look at differences from old config to new
				ii.) copy your config options to new file(s)
		3.) Load new database:
			a.) create new database with updated schema
				i.) convert old data into new schema
				ii.) fiddle with specific data modifications by hand
			b.) run db modifications by hand
				i.) fiddle with syntax changes
		4.) load website
		5.) go back to #2, rins, and repeat until it works again.
		6.) get back to work (after possibly hours of frustration)
	
	CS-PROJECT:
		1.) run "svn update" on your checkout.
		2.) load website
		3.) get back to work!


	In the event that Subversion isn't available, the traditional method of downloading & extracting is just fine, though prone to error.  Backup the old copy, extract the new one, and copy the config.xml file from the old directory into the new one.  Modified templates have to be dealt with manually, of course (which wouldn't be a problem if the Subversion option were used).

*Scripted Upgrades*
	Besides getting the newest version and features, there is another unique system incorporated into CS-Project: upgrades happen on-the-fly.  After an update occurs, the next time the website is loaded, any bug fixes or database changes happened automatically.  From schema changes to data fixes and anything in between, automatic upgrades ensure that problems are fixed quickly.

	“But why is this important?”  Imagine a situation where, for some reason, a glitch in some obscure piece of code somewhere causes an issue to be linked to a different project after you've already set it properly.  Somehow, issues just seem to move to random locations, or disappear, and you just don't know why... The next version fixes the problem.  You update your checkout and reload the page: suddenly, all your records are linked exactly the way they should have been.  *This is the power of scripted upgrades.*

*Database Conversions*
	One of the other features that was thought of from the start was the ability to automatically convert an existing project database to be used with CS-Project.  During the setup process, there is an option to connect to an existing database to convert from another database to CS-Project.



UPGRADING CS-PROJECT
--------

*DEFINITIONS*
Because the definition of a given term is sometimes inconsistent across software projects, I feel it is important to make their meanings as clear as possible to avoid confusion.

	* "version string": this is a string of numbers separated by dots, sometimes containing a suffix, to indicate a particular "variant" of CS-Project's codebase.  Example: "1.0.0-BETA1" is the first BETA version of release 1 (minor version 0, maintenance 0).
	
	* "version suffix": A version suffix indicates that the given version is not known to be stable.  They contain a number after the suffix to indicate how many of that release has been made (for 3 beta releases, the suffix would be "-BETA3").  An example of suffixes used by CS-Project:
		-- ALPHA: very unstable, lacking many user-friendly features, usually known contain bugs (some of which may be critical), and has generally only been tested by a very limited number of people.  Usually only available to hardcore developers & testers.
		-- BETA: appears to be stable, may lack some less-important features, but free of all known bugs.  These are usually available to anyone that seeks to test them.
		-- RC: this is a "release candidate".  All accepted feature requests have been implemented, and is free of all known bugs.  This is usually made available to the public, though not widely publicized.

	* "major version": a massive change to the interface and underlying architecture.  These changes are usually very apparent and usually require reading documentation to discover what has changed.

	* "minor version": incorporation of one or more feature requests.  Incorporates all prior changes and fixes.

	* "maintenance version": whenever one or more bugs are fixed, a maintenance release is created.  These sometimes incorporate minor feature requests deemed appropriate and fall within the scope of the current minor version.

*UPDATE FREQUENCY*
Constant work is being done on CS-Project to make it as powerful (and useful) as possible.  It is nice, however, to know roughly how often one should expect changes to occur:

	* All file releases are based on Subversion releases.
	* Major version releases:
		-- no set timeline is available for major releases.
	* Minor version releases:
		-- these should generally only happen every 1-12 months, depending upon the number of features requested.
	* Maintenance releases:
		-- created whenever a critical bug is fixed
		-- can sometimes happen up to several times per day

*AVAILABILITY*
	* Releases are available via Subversion immediately.
	* File releases will be made 
	* These releases are generally available as a file download once a month, depending on the number of changes that happened (if they contain critical bug fixes, updates are usually made available the same day they're fixed).


HOW TO OBTAIN UPDATES
---------------------

Whenever an existing installation is updated/upgraded, the database is updated to reflect the new version.  This sometimes involves scripted changes, which should always run seemlessly in the background.


*Subversion (the easy way)*
	* maintenance updates:
		-- update your install using "svn update" (via command line or a program)
		-- open/reload website to ensure the new version is reflected
	* major/minor versions:
		-- use "svn info" to determine the current release (the URL will have "releases/" followed by the major.minor version your installation is using)
		-- to switch to (for example) version 1.2, run the following command:
			"svn switch https://cs-project.svn.sourceforge.net/svnroot/cs-project/releases/1.2"
		-- open/reload website to ensure the new version is reflected.

*Manual Download*
	* download the latest copy from http://sf.net/projects/cs-project
	* backup your existing install
	* extract new version into a new directory
	* copy old changed files and the config.xml file into the new directory.
	* open/reload the website to ensure it reflects the new version.

