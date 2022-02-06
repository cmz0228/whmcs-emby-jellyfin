To run the module, one database table is needed.
Below is the table structure

CREATE TABLE `mod_emby_connect_user` (
  `id` int(11) NOT NULL,
  `serviceid` int(11) NOT NULL,
  `emby_userid` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

ALTER TABLE `mod_emby_connect_user`
  ADD PRIMARY KEY (`id`);

You can upload the install-table.php to whmcs root and hit once in the web url and delete the file.


Create two product custom fields
['Emby Username'];
['Emby Password'];
