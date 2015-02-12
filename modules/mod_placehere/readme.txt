Mod Placehere

Author: Eike Pierstorff
E-Mail: eike@diebesteallerzeiten.de
URL: http://www.diebesteallerzeiten.de/blog/

Version: 1.3.1
License: GPL 
A module to show Articles in module positions. Some documentation is here: http://diebesteallerzeiten.de/blog/category/manual/. Some options might be mutually exclusive. The module offers different templates and not every parameter is supported by every template. Tag support only works for the Tag module from http://www.joomlatags.org/

Todo
+ Backend translation (most of it us currently hardcoded)
+ try to make this compatible with JoomFish

Changelog

Version 1.3.1
+ 02/05/10 Bugfix Groups (access) for uncategorized articles, fixes als 'stdClass:$groups:undefined property" error
+ 02/05/10 Added option to disable frontend edit button for logged-in admins and editors 

Version 1.3.0
+ Renamed "default" template to "table based template" (since it's not the default anymore)
+ Added "order by publishing date"
+ Patched in Wayne Brockmans change to sort by hits
+ Added integration for Tags extension by joomlatag.org (module view is filtered by templates)
+ You can now enter a range of ids into the id-field (1-3,6-9 will become 1,2,3,6,7,8,9)
+ Added include path to icons (pdf etc)

Version 1.2.2
+ 02/19/09 fixed sql to respect timezone setting (undo change from 1.2.1)
+ 02/19/09 included template that respects article parameters instead of module parameters
+ 02/19/09 changed gallery mode parameter default to "disabled"
+ 02/19/09 changed template parameter default to "beez", output mode default to "div"
+ 02/20/09 fixed bug in ordering 

Version 1.2.1 
+ 04/01/09 instead of using Joomla functions to find the current date the query now uses MysQls Now()-Function
+ 04/91/09 Related Article Feature von Keywan Ghadami [keywan.ghadami@googlemail.com]

Version 1.1.1 
+ 27/12/08 bugfix: parameter values for gallery mode
+ 27/12/08 edit in frontend
+ 27/12/08 "zebra striping" of module content possible via classes "even" and "odd" on module content rows
+ 27/12/08 bugfix: renamed "content item" to "article" to be consistend with 1.5 terminology
+ 27/12/08 bugfix: links for content type "articles" with "link to category" enabled

Version: 1.1.0 Beta
+	02/11/08 gallery Mode
	
Version: 1.0.1
+	10/10/08 Strip HTML is now an option indepentenly from trim text
+	10/10/08 borrowed the trim function from Michael Kellys (http://www.conurestudios.com) Wordpress Plugin, this respects HTML
+	14/08/08 finally introduced a version number, so this is now 1.0. I will follow the x.x.x major.minor.bugfix scheme
	for version numbers
+	Tested with Joomla 1.5.6 on Windows Vista Home/Apache/PHP 4.8
