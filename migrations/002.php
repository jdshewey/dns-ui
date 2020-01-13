<?php
$migration_name = 'Initial setup, converted to migration';

// This migration's execution is conditional on the tables not already existing.
// This is because the migration system was not originally in place and admins
// were instructed to manually run an SQL script to create this initial setup.
try {
	$this->database->exec('SELECT * FROM config');
} catch(PDOException $e) {

        $this->database->exec('
        CREATE TABLE `change` (
                id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
                changeset_id integer NOT NULL,
                `before` blob,
                after blob
        )
        ');

        $this->database->exec('
        CREATE TABLE changeset (
                id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
                zone_id integer NOT NULL,
                author_id integer NOT NULL,
                change_date timestamp NOT NULL,
                comment text,
                deleted integer DEFAULT 0 NOT NULL,
                added integer DEFAULT 0 NOT NULL,
                requester_id integer
        )
        ');

        $this->database->exec('
        CREATE TABLE config (
                id integer NOT NULL PRIMARY KEY,
                default_soa_template integer,
                default_ns_template integer
        )
        ');
        $this->database->exec('INSERT INTO `config` (id) VALUES (1)');

        $this->database->exec('
        CREATE TABLE ns_template (
                id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
                name varchar(256) NOT NULL UNIQUE,
                nameservers text NOT NULL
        )
        ');

        $this->database->exec('
        CREATE TABLE pending_update (
                id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
                zone_id integer NOT NULL,
                author_id integer,
                request_date timestamp NOT NULL,
                raw_data blob NOT NULL
        )
        ');

	        $this->database->exec('
        CREATE TABLE soa_template (
                id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
                name varchar(256) NOT NULL UNIQUE,
                primary_ns text NOT NULL,
                contact text NOT NULL,
                refresh integer NOT NULL,
                retry integer NOT NULL,
                expire integer NOT NULL,
                default_ttl integer NOT NULL,
                soa_ttl integer
        )
        ');

        $this->database->exec('
        CREATE TABLE user (
                id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
                uid varchar(256) UNIQUE,
                name text,
                email text,
                auth_realm ENUM ("local","LDAP"),
                active integer,
                admin integer DEFAULT 0 NOT NULL,
                developer integer DEFAULT 0 NOT NULL,
                csrf_token text
        )
        ');

        $this->database->exec('
        CREATE TABLE user_alert (
                id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id integer,
                class text,
                content text,
                escaping integer
        )
        ');

        $this->database->exec('
        CREATE TABLE zone (
                id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
                pdns_id varchar(256) UNIQUE,
                name text,
                serial bigint,
                active boolean DEFAULT true NOT NULL,
                account text
        )
        ');

        $this->database->exec("
        CREATE TABLE zone_access (
                zone_id integer NOT NULL PRIMARY KEY,
                user_id integer NOT NULL,
                level ENUM ('administrator', 'operator') NOT NULL
        )
        ");

	$this->database->exec('
	ALTER TABLE change
		ADD CONSTRAINT change_changeset_id_fkey FOREIGN KEY (changeset_id) REFERENCES changeset(id) ON DELETE CASCADE;
	');

	$this->database->exec('
	ALTER TABLE changeset
		ADD CONSTRAINT changeset_author_id_fkey FOREIGN KEY (author_id) REFERENCES "user"(id),
		ADD CONSTRAINT changeset_requester_id_fkey FOREIGN KEY (requester_id) REFERENCES "user"(id),
		ADD CONSTRAINT changeset_zone_id_fkey FOREIGN KEY (zone_id) REFERENCES zone(id) ON DELETE CASCADE;
	');

	$this->database->exec('
	ALTER TABLE config
		ADD CONSTRAINT config_default_ns_template_fkey FOREIGN KEY (default_ns_template) REFERENCES ns_template(id) ON DELETE SET NULL;
	');

	$this->database->exec('
	ALTER TABLE pending_update
		ADD CONSTRAINT pending_change_zone_id_fkey FOREIGN KEY (zone_id) REFERENCES zone(id) ON DELETE CASCADE;
	');

	$this->database->exec('
	ALTER TABLE user_alert
		ADD CONSTRAINT user_alert_user_id_fkey FOREIGN KEY (user_id) REFERENCES "user"(id);
	');

	$this->database->exec('
	ALTER TABLE zone_access
		ADD CONSTRAINT zone_admin_user_id_fkey FOREIGN KEY (user_id) REFERENCES "user"(id) ON DELETE CASCADE,
		ADD CONSTRAINT zone_admin_zone_id_fkey FOREIGN KEY (zone_id) REFERENCES zone(id) ON DELETE CASCADE;
	');
}
