<?php


namespace Shared\Admin;

use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\Exporter\DataSourceInterface;
use Sonata\DoctrineORMAdminBundle\Exporter\DataSource;
use Sonata\Exporter\Source\DoctrineORMQuerySourceIterator;

use Kantine\Admin\OrderAdmin;

class ManyToManyDataSource implements DataSourceInterface
{
    public function __construct(private readonly DataSource $dataSource) {}

    public function createIterator(ProxyQueryInterface $query, array $fields): \Iterator
    {
        /** @var DoctrineORMQuerySourceIterator $iterator */
        $iterator = $this->dataSource->createIterator($query, $fields);
        $iterator->setDateTimeFormat('d.m.Y H:i:s');

        return $iterator;
    }
}
