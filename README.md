# VV DB

VV database abstraction layer with query builder and DB structure models.

## Installation

This is the basic package. To use it with concrete DBMS install one of these drivers using [Composer](https://getcomposer.org):
- [db-mysqli](https://packagist.org/packages/phpvv/db-mysqli) - MySQL driver over MySQLi extension;
- [db-pdo](https://packagist.org/packages/phpvv/db-pdo) - PostgreSQL (and other) driver over PDO extension;
- [db-oci](https://packagist.org/packages/phpvv/db-oci) - Oracle driver over oci8 extension.

```shell
composer require phpvv/db-mysqli
# or
composer require phpvv/db-pdo
# or
composer require phpvv/db-oci
```

## Big Select Example

[big-select.php](https://github.com/phpvv/db-examples/blob/master/examples/big-select.php) in [DB Examples Project](https://github.com/phpvv/db-examples):

```php
use VV\Db\Param;
use VV\Db\Sql;

$db = \App\Db\MainDb::instance();

$categoryId = 102;
$stateParam = Param::int(value: 1, name: 'state');

$query = $db->tbl->product
    ->select('title', 'b.title brand', 'price', 'c.title as color') // SELECT p.title, ... FROM tbl_product as p
    ->join($db->tbl->brand)                                         // INNER JOIN tbl_brand as b ON b.brand_id = p.brand_id
    ->left($db->tbl->color, 'p')                                    // LEFT JOIN tbl_color as c ON c.color_id = p.color_id
    // ->left($db->tbl->color, on: 'c.color_id = p.color_id', alias: 'c') // same as above
    ->where( // WHERE ...
        Sql::condition()
            ->exists(                                               // WHERE EXISTS (
                $db->tbl->productsCategories->select('1')               // SELECT 1 FROM tbl_products_categories pc
                ->where('`pc`.`product_id`=`p`.`product_id`')           // WHERE ("pc"."product_id"="p"."product_id")
                ->where([
                    'category_id' => $categoryId,                       // AND pc.category_id = :p1
                    'state' => $stateParam                              // AND pc.state = :state
                ])
            )                                                       // )
            ->and('state')->eq($stateParam)                         // AND p.state = :state
            // ->and('title')->like('Comp%')
            ->and('title')->startsWith('Comp')                      // AND p.title LIKE :p2 -- 'Comp%'
            ->and('price')
                ->gte(1000)                                         // AND p.price >= :p3
                ->and
                ->lte(2000)                                         // AND p.price <= :p4
            ->and('weight')->between(7000, 15000)                   // AND p.weight BETWEEN :p5 AND :p6
            ->and(                                                  // AND (
                Sql::condition('width') // nested condition
                    ->lt(500)                                           // p.width > :p7
                    ->or                                                // OR
                    ->isNull()                                          // p.width IS NULL
            )                                                       // )
            ->and('c.color_id')->in(1, 5, null)                     // AND (c.color_id IN (:p8, :p9) OR c.color_id IS NULL)
            ->and('brand_id')->not->in(3, 4)                        // AND (p.brand_id NOT IN (:p10, :p11) OR p.brand_id IS NULL)
            ->and('height')->isNotNull()                            // AND p.height IS NOT NULL
    )
    // ->groupBy(...)
    // ->having(...)
    ->orderBy(                                                      // ORDER BY
        Sql::case('p.color_id')
            ->when(5)->then(1)                                      // color_id = 5 - first
            ->when(1)->then(2)                                      // color_id = 1 - second
            ->else(3),                                              // other colors at the end - first
        // Sql::case()
        //     ->when(Sql::condition('p.color_id')->eq(5))->then(1)    // same CASE as above
        //     ->when(['p.color_id' => 1])->then(2)
        //     ->else(3),
        '-price',                                                   // price DESC,
        'title'                                                     // title ASC
    )
    ->limit(10);                                                    // LIMIT 10


echo "SQL:\n", $query->toString(), "\n\n";

// $rows = $query->rows;
// $rows = $query->rows(\VV\Db::FETCH_NUM);
// $rows = $query->rows(null, keyColumn: 'product_id', decorator: function (&$row, &$key) => { /*...*/ });
// $row = $query->row;
// $row = $query->row(\VV\Db::FETCH_NUM | \VV\Db::FETCH_LOB_NOT_LOAD);
// $title = $query->column;
// $brand = $query->column(1);

/*
$statement = $query->prepare();
print_r($statement->result()->rows);
$stateParam->setValue(0);
print_r($statement->result()->rows);
$statement->close();
*/

echo "rows:\n";
foreach ($query->result as $row) {
    print_r($row);
}
```

Output:
```
SQL:
SELECT "p"."title", "b"."title" "brand", "p"."price", "c"."title" "color"
FROM "tbl_product" "p"
    JOIN "tbl_brand" "b" ON ("b"."brand_id" = "p"."brand_id")
    LEFT JOIN "tbl_color" "c" ON ("c"."color_id" = "p"."color_id")
WHERE (
    EXISTS (
        SELECT 1
        FROM "tbl_products_categories" "pc"
        WHERE ("pc"."product_id" = "p"."product_id")
            AND "category_id" = :p1
            AND "state" = :state
    )
    AND "p"."state" = :state
    AND "p"."title" LIKE :p2
    AND "p"."price" >= :p3
    AND "p"."price" <= :p4
    AND "p"."weight" BETWEEN :p5 AND :p6
    AND ("p"."width" < :p7 OR "p"."width" IS NULL)
    AND ("c"."color_id" IN (:p8, :p9) OR "c"."color_id" IS NULL)
    AND ("p"."brand_id" NOT IN (:p10, :p11) OR "p"."brand_id" IS NULL)
    AND "p"."height" IS NOT NULL
)
ORDER BY (
        CASE "p"."color_id"
            WHEN 5 THEN 1
            WHEN 1 THEN 2
            ELSE 3
        END
    ) NULLS FIRST,
    "price" DESC NULLS LAST,
    "title" NULLS FIRST
LIMIT 10

rows:
Array
(
    [title] => Computer 11
    [brand] => Brand 2
    [price] => 1500.00
    [color] => White
)
Array
(
    [title] => Computer 10
    [brand] => Brand 1
    [price] => 1000.00
    [color] => Black
)
Array
(
    [title] => Computer 12
    [brand] => Brand 1
    [price] => 1200.00
    [color] =>
)
```


## Big Transaction Example

[big-transaction.php](https://github.com/phpvv/db-examples/blob/master/examples/big-transaction.php) in [DB Examples Project](https://github.com/phpvv/db-examples):

```php
use VV\Db\Param;

$db = \App\Db\Main::instance();

$userId = 1;
$cart = [
    // productId => quantity
    10 => 1,
    20 => 2,
    40 => 3,
];


$productIter = $db->tbl->product->select('product_id', 'price')
    ->whereIdIn(...array_keys($cart))
    ->result(\VV\Db::FETCH_NUM);

$txn = $db->startTransaction();
try {
    $orderId = $db->tbl->order->insert()
        ->set(['user_id' => $userId])
        ->insertedId($txn);

    $totalAmount = 0;
    $productIterExtended = (function () use ($productIter, $cart, &$totalAmount) {
        foreach ($productIter as [$productId, $price]) {
            yield [$productId, $price, $quantity = $cart[$productId]];
            $totalAmount += $price * $quantity;
        }
    })();

    // variants:
    switch (2) {
        case 1:
            // don't care about performance
            foreach ($productIterExtended as [$productId, $price, $quantity]) {
                $db->tbl->orderItem->insert()
                    ->set([
                        'order_id' => $orderId,
                        'product_id' => $productId,
                        'price' => $price,
                        'quantity' => $quantity,
                    ])
                    ->exec($txn);
            }
            break;
        case 2:
            // multi values insert in one query
            $insertItemQuery = $db->tbl->orderItem->insert()
                ->fields('order_id', 'product_id', 'price', 'quantity');

            foreach ($productIterExtended as [$productId, $price, $quantity]) {
                $insertItemQuery->values($orderId, $productId, $price, $quantity);
            }
            $insertItemQuery->exec($txn);
            break;
        case 3:
            // prepared query
            $prepared = $db->tbl->orderItem->insert()
                ->set([
                    'order_id' => Param::int($orderId),
                    'product_id' => $productIdParam = Param::chr(size: 16),
                    'price' => $priceParam = Param::chr(size: 16),
                    'quantity' => $quantityParam = Param::chr(size: 16),
                ]);

            foreach ($productIterExtended as [$productId, $price, $quantity]) {
                $productIdParam->setValue($productId);
                $priceParam->setValue($price);
                $quantityParam->setValue($quantity);

                $prepared->exec($txn);
            }
            break;
    }

    $db->tbl->order->update()
        ->set(['amount' => $totalAmount])
        ->whereId($orderId)
        // ->exec() // throws an exception that you are trying to execute statement outside of transaction started for current connection
        ->exec($txn);

    // you can execute important statement in transaction free connection
    $db->tbl->log->insert()
        ->set(['title' => "new order #$orderId"])
        ->setConnection($db->getTransactionFreeConnection()) // set new connection for query
        ->exec();

    // throw new \RuntimeException('Test transactionFreeConnection()');

    $txn->commit();
} catch (\Throwable $e) {
    $txn->rollback();
    /** @noinspection PhpUnhandledExceptionInspection */
    throw $e;
}
```


## Basics

### Using only `Connection` without schema model representation

Example ([connection.php](https://github.com/phpvv/db-examples/blob/master/examples/connection.php)):

```php
use APP\DB\MAIN as CONF;
use VV\Db\Connection;
use VV\Db\Pdo\Driver;

// $driver = new \VV\Db\Oci\Driver;
// $driver = new \VV\Db\Mysqli\Driver;
$driver = new Driver(Driver::DBMS_POSTGRES);

$connection = new Connection($driver, CONF\HOST, CONF\USER, CONF\PASSWD, CONF\DBNAME);
// $connection->connect(); // auto connect on first query is enabled by default

// all variants do same job:
$queryString = 'SELECT product_id, title FROM tbl_product WHERE price > :price';
$result = $connection->query($queryString, ['price' => 100]);
// or
$result = $connection->prepare($queryString)->bind(['price' => 100])->result();
// or
$result = $connection->select('product_id', 'title')->from('tbl_product')->where('price > ', 100)->result();

print_r($result->rows);
```

### Using DB Model(s)

#### Configuration

At start, it is needed to create somewhere `class <MyNameOf>Db extends \VV\Db` and implement one abstract method `createConnection()`.
Example ([App/Db/MainDb.php](https://github.com/phpvv/db-examples/blob/master/App/Db/MainDb.php)):

```php
namespace App\Db;

use APP\DB\MAIN as CONF;
use VV\Db\Connection;
use VV\Db\Pdo\Driver;

/**
 * @method MainDb\TableList tables()
 * @method MainDb\ViewList views()
 * @property-read MainDb\TableList $tbl
 * @property-read MainDb\TableList $vw
 */
class MainDb extends \VV\Db {

    public function createConnection(): Connection {
        $driver = new Driver(Driver::DBMS_POSTGRES);

        return new Connection($driver, CONF\HOST, CONF\USER, CONF\PASSWD, CONF\DBNAME);
    }
}
```

#### Model Generation

Just run this code ([gen-db-model.php](https://github.com/phpvv/db-examples/blob/master/examples/gen-db-model.php)):

```php
use App\Db\MainDb;
use VV\Db\Model\Generator\ModelGenerator;

(new ModelGenerator(MainDb::instance()))->build();
```
DB schema representation classes will be created in the `App\Db\MainDb` folder.

#### Usage

Example ([db-model.php](https://github.com/phpvv/db-examples/blob/master/examples/db-model.php)):

```php
use App\Db\MainDb;

$db = MainDb::instance();

$products = $db->tbl->product
    ->select('product_id', 'b.title brand', 'title', 'price')
    ->join($db->tbl->brand)
    ->where('brand_id', 1)
    ->where('price >', 100)
    ->rows;

print_r($products);
```


## Select

### Create [`SelectQuery`](./Db/Sql/SelectQuery.php)

There are several variants to create `SelectQuery` ([select.php](https://github.com/phpvv/db-examples/blob/master/examples/select.php)):

```php
use App\Db\MainDb;

$db = MainDb::instance();
$connection = $db->getConnection(); // or $db->getFreeConnection();
// $connection = new \VV\Db\Connection(...);

$columns = ['product_id', 'b.title brand', 'title', 'price'];

// from Connection directly
$selectQuery = $connection->select(...$columns)->from('tbl_product');

// from Db
$selectQuery = $db->select(...$columns)->from('tbl_product');

// from Table
$selectQuery = $db->tbl->product->select(...$columns);
```

### Columns Clause

Method `select(...)` (see above) returns [`SelectQuery`](./Db/Sql/SelectQuery.php) object. You can change columns using methods `SelectQuery::columns()` or `SelectQuery::addColumns()`:

```php
$query = $db->tbl->product->select()
    ->columns('product_id', 'brand_id')
    ->addColumns('title', 'price');

print_r($query->rows);
```

All these methods accepts `string` or [`Expression`](./Db/Sql/Expressions/Expression.php) interface as each column. So you can do something like this:

```php
$query = $db->tbl->product->select(
    'product_id',
    'title product',
    $db->tbl->brand
        ->select('title')
        ->where('b.brand_id = p.brand_id')
        ->as('brand'),
    'price',
    Sql::case()
        ->when(['price >= ' => 1000])->then(1)
        ->else(0)
        ->as('is_expensive'),
);

print_r($query->rows);
```

### From Clause

To set table or view to query you can call `from()` method or create query directly from [`Table`](./Db/Model/Table.php) or [`View`](./Db/Model/View.php):
```php
$query = $db->tbl->product->select(/*...*/);
// or
$query = $db->select(/*...*/)->from($db->tbl->product); // same as above
// or
$query = $db->select(/*...*/)->from('tbl_product'); // not same as above regarding JOIN clause
```

By default, alias of table (or view) consists of first letters of each word of table (or view) name without prefix (`tbl_`,`t_`, `vw_`, `v_`). For example: `tbl_order` -> `o`, `tbl_order_item` -> `oi`.

To change table alias, call `mainTableAs()` method of query:
```php
$query = $db->tbl->product->select(/*...*/)->mainTableAs('prod');
```

### Join Clause

To set JOIN clause use these methods: `join()`, `left()`, `right()`, `full()`.

Example:
```php
$query = $db->tbl->orderItem->select(/*...*/)  // SELECT ... FROM "tbl_order_item" "oi"
    ->join($db->tbl->order)                    // JOIN "tbl_order" "o" ON "o"."order_id" = "oi"."order_id"
    ->left($db->tbl->orderState)               // LEFT JOIN "tbl_order_state" "os" ON "os"."state_id" = "o"."state_id"
    ->join($db->tbl->product, 'oi')            // JOIN "tbl_product" "p" ON "p"."product_id" = "oi"."product_id"
    ->where('o.state_id', 1);                  // WHERE "o"."state_id" = ?
```

By default, table joins to previous table by primary key. Default alias of table is first letters of each word of table name. You can change ON condition (second parameter) and alias (third parameter):
```php
$query = $db->tbl->orderItem->select(/*...*/)
    ->join(
        $db->tbl->order,
        'order.order_id=oi.order_id', // string
        'order'
    )
    ->left(
        $db->tbl->orderState,
        Sql::condition()              // Condition object
            ->and('os.state_id')->eq(Sql::expression('o.state_id'))
            ->and('os.state_id')->eq(1)
    );
```

#### ON condition shortcuts

Specify alias of table to which join is needed:
```php
$query = $db->tbl->orderItem->select(/*...*/)
    ->join($db->tbl->order)
    ->join($db->tbl->product, 'oi'); // join to tbl_order_item (not tbl_order) by product_id field
```

Specify column of table to which join is needed:
```php
$query = $db->tbl->orderItem->select(/*...*/)
    ->join($db->tbl->order, '.foo_id'); // "o"."order_id" = "oi"."foo_id"
```

Specify alias and column of table to which join is needed:
```php
$query = $db->tbl->orderItem->select(/*...*/)
    ->join($db->tbl->order)
    ->join($db->tbl->product, 'oi.foo_id'); // "p"."product_id" = "oi"."foo_id"
```

### Where Clause

## Insert

*Coming soon...*

## Update

*Coming soon...*

## Delete

*Coming soon...*

## Condition

*Coming soon...*

## Case Expression

*Coming soon...*
