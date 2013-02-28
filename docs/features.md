## CS-Project Feature Overview


### Simplicity

CS-Project is built on a very simple premise: Project managers manage projects.  There are various types of issues associated with those projects (bugs, feature requests, etc).  Each project and issue have tasks associated with them.  Everyone is interested in how long things take.

CS-Project incorporates all of these principles.  Projects are a broad indicator of the various pieces of a system.  Large projects can be broken into smaller parts (sometimes referred to as “milestones”) to help focus efforts.  New issues, including bugs and feature requests, can be added to a project to help maintain a list of all the things that are requested or have been found to be problematic.  Tasks create a breakdown of things that need to be accomplished.

To further simplify things, CS-Project doesn't tie its users down to a specific way to use it: it can be easily used strictly as a Trouble Ticket (Help Desk) system, or just projects, or both.


### Easy Setup

CS-Project is very easy to setup.  Once the webserver accepts connections to the website name of your choosing (like "http://project.crazedsanity.com"), just go to it's address, and the setup program will start immediately. Each step explains exactly what will happen, provides defaults for nearly every item, and shows the output from every completed step.  No need to run any scripts on a command line, loading things manually, or anything else: it's all done for you.

Normal installations of specialized websites that are downloaded off the 'net require steps similar to the following:
	1.) Download
	2.) extract tarball/zip file
	3.) edit source file/config files by hand (relying on the developer's notes to guide)
	4.) tweak & load database schema by hand
	5.) open website in browser
	6.) go back to step #3, rinse, and repeat until it seems to work
Setting up CS-Project:
	1.) git clone https://github.com/crazedsanity/cs-project.git
	2.) open website in browser
	3.) follow step-by-step instructions until install is complete


### Upgradeability

CS-Project was built with the understanding that no piece of software is perfect.  New features are implemented.  Bugs are fixed.  Backend systems are tweaked.  Security issues are patched.

Upgrading is a pretty big topic, so just read the [upgrading](upgrading.md) file in this directory.
