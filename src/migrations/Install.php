<?php
/**
 * Out for Craft 3
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) Ether Creative
 */

namespace ether\out\migrations;

use craft\db\Migration;


/**
 * Class Install
 *
 * @author  Ether Creative
 * @package ether\out\migrations
 * @since   1.0.0
 */
class Install extends Migration
{

	public function safeUp ()
	{
		if (!$this->db->tableExists('{{%out_exports}}'))
		{
			// create the exports table
			$this->createTable('{{%out_exports}}', [
				'id'            => $this->integer()->notNull(),
				'elementType'   => $this->string()->notNull(),
				'filter'        => $this->json()->notNull(),
				'fieldLayoutId' => $this->integer()->notNull(),
				'dateCreated'   => $this->dateTime()->notNull(),
				'dateUpdated'   => $this->dateTime()->notNull(),
				'uid'           => $this->uid(),
				'PRIMARY KEY(id)',
			]);

			// give it a FK to the elements table
			$this->addForeignKey(
				$this->db->getForeignKeyName('{{%out_exports}}', 'id'),
				'{{%out_exports}}', 'id', '{{%elements}}', 'id', 'CASCADE', null
			);
		}

	}

}