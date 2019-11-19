CREATE TABLE tx_bwemail_domain_model_contactsource (

	uid               int(11) NOT NULL auto_increment,
	pid               int(11) DEFAULT '0' NOT NULL,

	record_type       varchar(255) DEFAULT '' NOT NULL,
	name              varchar(255) DEFAULT '' NOT NULL,
	fe_users          int(11) unsigned DEFAULT '0' NOT NULL,
	fe_user_groups    int(11) unsigned DEFAULT '0' NOT NULL,
	fe_pid            int(11) unsigned DEFAULT '0' NOT NULL,
	fe_recipient_type int(11) unsigned DEFAULT '0' NOT NULL,

	tstamp            int(11) unsigned DEFAULT '0' NOT NULL,
	crdate            int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id         int(11) unsigned DEFAULT '0' NOT NULL,
	deleted           smallint(5) unsigned DEFAULT '0' NOT NULL,
	hidden            smallint(5) unsigned DEFAULT '0' NOT NULL,

	sys_language_uid  int(11) DEFAULT '0' NOT NULL,
	l10n_parent       int(11) DEFAULT '0' NOT NULL,
	l10n_diffsource   mediumblob,
	l10n_state        text,

	PRIMARY KEY (uid),
	KEY               parent (pid),
	KEY language (l10n_parent,sys_language_uid)

);

CREATE TABLE tx_bwemail_domain_model_contactsource_fe_users_mm (
	uid_local       int(11) unsigned DEFAULT '0' NOT NULL,
	uid_foreign     int(11) unsigned DEFAULT '0' NOT NULL,
	sorting         int(11) DEFAULT '0' NOT NULL,
	sorting_foreign int(11) DEFAULT '0' NOT NULL,

	KEY             uid_local (uid_local),
	KEY             uid_foreign (uid_foreign)
);

CREATE TABLE tx_bwemail_domain_model_contactsource_fe_groups_mm (
	uid_local       int(11) unsigned DEFAULT '0' NOT NULL,
	uid_foreign     int(11) unsigned DEFAULT '0' NOT NULL,
	sorting         int(11) DEFAULT '0' NOT NULL,
	sorting_foreign int(11) DEFAULT '0' NOT NULL,

	KEY             uid_local (uid_local),
	KEY             uid_foreign (uid_foreign)
);

CREATE TABLE tx_bwemail_domain_model_maillog (
	uid               int(11) NOT NULL auto_increment,
	pid               int(11) DEFAULT '0' NOT NULL,

	status int(11)  unsigned DEFAULT '0' NOT NULL,
	send_date int(11) DEFAULT '0' NOT NULL,
	recipient_address varchar(255) DEFAULT '' NOT NULL,
	recipient_name varchar(255) DEFAULT '' NOT NULL,
	subject varchar(255) DEFAULT '' NOT NULL,
	body text,
	sender_address varchar(255) DEFAULT '' NOT NULL,
	sender_name varchar(255) DEFAULT '' NOT NULL,
	sender_replyto varchar(255) DEFAULT '' NOT NULL,

	crdate            int(11) unsigned DEFAULT '0' NOT NULL,
	PRIMARY KEY (uid),
	KEY               parent (pid)
);
