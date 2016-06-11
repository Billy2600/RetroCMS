# RetroCMS
Hello! If you are reading this, you've stumbled upon RetroCMS. If you didn't come from retrooftheweek.net, then you should know that this CMS was created to power retrooftheweek.net

## This software is not for people who don't know what they are doing
What do I mean by that? Using this software requires knowledge of MySQL management, for example. To even install this software, you will have to know how to import a MySQL database. I can create an installer script, but only if there is enough interest.

# How to install
Firstly, you must set up the variables in config.php, hopefully they are self-explanitory

Then you must create the MySQL tables. They must begin with the prefix you set in config.php. You can do this by importing the database.sql file. **If you use a prefix other than ret_ for tables, you will need to find-and-replace in the database.sql file.**