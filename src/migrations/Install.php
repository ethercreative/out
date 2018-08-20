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
				'title'         => $this->string()->notNull(),

				'elementType'   => $this->string()->notNull(),
				'elementSource' => $this->string()->notNull(),

				'search'        => $this->string()->null(),
				'limit'         => $this->integer()->null(),
				'startDate'     => $this->dateTime()->null(),
				'endDate'       => $this->dateTime()->null(),

				'fieldSettings' => $this->json()->notNull(),
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

	public function safeDown ()
	{
		$this->dropTableIfExists('{{%out_exports}}');
	}

}