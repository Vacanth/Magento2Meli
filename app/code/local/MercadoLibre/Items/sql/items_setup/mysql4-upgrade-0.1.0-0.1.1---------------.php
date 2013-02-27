<?php

try {
$installer = $this;

$installer->startSetup();

$installer->run("
ALTER TABLE {$this->getTable('meli_category_update')}
	ADD `new_colum` ENUM( 'NO', 'YES', 'ACK', 'CNF' ) NOT NULL DEFAULT 'NO';
    ");

    $installer->endSetup();
} catch (Exception $e) {
print_r($e);
    die;
}