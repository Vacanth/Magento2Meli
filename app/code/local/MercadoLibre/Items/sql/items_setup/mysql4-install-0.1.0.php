<?php

try {
$installer = $this;

$installer->startSetup();

$installer->run("

-- DROP TABLE IF EXISTS {$this->getTable('meli_category_update')};
CREATE TABLE {$this->getTable('meli_category_update')} (
  `update_id` int(11) NOT NULL AUTO_INCREMENT,
  `created_datetime` datetime NOT NULL,
  `run_datetime` datetime NOT NULL,
  PRIMARY KEY (`update_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- DROP TABLE IF EXISTS {$this->getTable('meli_categories')};
CREATE TABLE {$this->getTable('meli_categories')} (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `meli_category_id` varchar(100) DEFAULT NULL,
  `meli_category_name` varchar(200) DEFAULT NULL,
  `site_id` varchar(100) DEFAULT NULL,
  `has_attributes` tinyint(1) NOT NULL DEFAULT '0',
  `root_id` varchar(20) NOT NULL DEFAULT '0',
  `listing_allowed` enum(20) NOT NULL DEFAULT '0',
  `root_id` varchar(20) NOT NULL DEFAULT '0',
  
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- DROP TABLE IF EXISTS {$this->getTable('meli_category_attributes')};
CREATE TABLE {$this->getTable('meli_category_attributes')} (
  `attribute_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `meli_attribute_id` varchar(200) DEFAULT NULL,
  `meli_attribute_name` varchar(100) DEFAULT NULL,
  `meli_attribute_type` varchar(200) DEFAULT NULL,
  `required` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`attribute_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- DROP TABLE IF EXISTS {$this->getTable('meli_category_attribute_values')};
CREATE TABLE {$this->getTable('meli_category_attribute_values')} (
  `value_id` int(11) NOT NULL AUTO_INCREMENT,
  `attribute_id` int(11) NOT NULL,
  `meli_value_id` varchar(200) DEFAULT NULL,
  `meli_value_name` varchar(100) DEFAULT NULL,
  `meli_value_name_extended` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`value_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


INSERT INTO `newsletter_template` (`template_id`, `template_code`, `template_text`, `template_text_preprocessed`, `template_styles`, `template_type`, `template_subject`, `template_sender_name`, `template_sender_email`, `template_actual`, `added_at`, `modified_at`) VALUES
(NULl, 'MELI_CRON_NOTIFICATION_EMAIL', '<p>Follow this link to unsubscribe</p>\r\n<!-- This tag is for unsubscribe link  -->\r\n<p><a href=\"{{var subscriber.getUnsubscriptionLink()}}\">{{message}}</a></p>', NULL, NULL, 2, 'Cron Notification Mail', 'CustomerSupport', 'support@ml.com', 1, '2013-02-22 08:16:02', '2013-02-22 08:16:02');

-- DROP TABLE IF EXISTS {$this->getTable('meli_categories_filter')};
CREATE TABLE {$this->getTable('meli_categories_filter')}  (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `meli_category_id` varchar(100) DEFAULT NULL,
  `meli_category_name` varchar(200) DEFAULT NULL,
  `site_id` varchar(100) DEFAULT NULL,
  `has_attributes` tinyint(1) NOT NULL DEFAULT '0',
  `root_id` varchar(20) NOT NULL DEFAULT '0',
  `listing_allowed` enum('1','0') NOT NULL DEFAULT '0',
  `buying_allowed` enum('1','0') NOT NULL DEFAULT '0',
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


");



    $installer->endSetup();
} catch (Exception $e) {
print_r($e);
    die;
}