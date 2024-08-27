<?php
use Phinx\Migration\AbstractMigration;

class CreatePaymentsTable extends AbstractMigration
{
    public function change()
    {
        $table = $this->table('payments');
        $table->addColumn('order_id', 'integer')
            ->addColumn('stripe_charge_id', 'string')
            ->addColumn('amount', 'decimal', ['precision' => 10, 'scale' => 2])
            ->addColumn('currency', 'string', ['limit' => 3])
            ->addColumn('created_at', 'datetime')
            ->addColumn('updated_at', 'datetime')
            ->create();
    }
}
