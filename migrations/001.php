<?php
$migration_name = "Add migration support";

$this->database->exec("
CREATE TABLE IF NOT EXISTS migration (
    id integer NOT NULL,
    name text NOT NULL,
    applied timestamp NOT NULL,
    CONSTRAINT migration_pkey PRIMARY KEY (id)
)
");
