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
                    'product_id' => $productIdParam = Param::str(size: 16),
                    'price' => $priceParam = Param::str(size: 16),
                    'quantity' => $quantityParam = Param::str(size: 16),
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

There are several variants to create `SelectQuery` ([create-select-query.php](https://github.com/phpvv/db-examples/blob/master/examples/select/01.create-select-query.php#L26-L37)):

```php
use App\Db\MainDb;

$db = MainDb::instance();
$connection = $db->getConnection(); // or $db->getFreeConnection();
// $connection = new \VV\Db\Connection(...);

$columns = ['product_id', 'b.title brand', 'title', 'price'];

// from `Connection` directly:
$selectQuery = $connection->select(...$columns)->from('tbl_product');
// from `Db`:
$selectQuery = $db->select(...$columns)->from('tbl_product');
// from `Table` (recommended):
$selectQuery = $db->tbl->product->select(...$columns);
```

### Fetch query result

#### From [`Result`](./Db/Result.php)

Fetch single row or column ([execute-select-query.php](https://github.com/phpvv/db-examples/blob/master/examples/select/02.execute-select-query.php#L24-L31)):
```php
$query = $db->tbl->product->select('product_id', 'title')->whereId(10);

$row = $query->result->row/*($flags)*/);
$productId = $query->result->column/*($columnIndex[, $flags])*/);
```

Fetch all rows ([execute-select-query.php](https://github.com/phpvv/db-examples/blob/master/examples/select/02.execute-select-query.php#L36-L53)):
```php
$query = $db->tbl->product->select('product_id', 'title', 'b.title brand')->join($db->tbl->brand)->limit(3);

$result = $query->result;
while (['product_id' => $productId, 'title' => $title] = $result->fetch()) {
    echo "$productId: $title\n";
}
// or
$rowIterator = $query->result(Db::FETCH_NUM);
foreach ($rowIterator as [$productId, $title, $brand]) {
    echo "$productId: $brand $title\n";
}
// or
$rows = $query->result->rows/*($flags)*/;
// or
$assoc = $query->result()->assoc/*(keyColumn: 'product_id'[, valueColumn: 'title'])*/;
//
```

You can set fetch mode flags to `fetch()`, `row()`, `rows()`, `column()`:

```php
use VV\Db;

$rows = $query->rows(
    Db::FETCH_ASSOC   // column name as key; default fetch mode (if $flags === null)
    | Db::FETCH_NUM   // column index as key
    | Db::FETCH_LOB_OBJECT // return LOB object instead of LOB content (only for Oracle yet)
);
```

Fetch result directly from query ([execute-select-query.php](https://github.com/phpvv/db-examples/blob/master/examples/select/02.execute-select-query.php#L58-L85)):
```php
$query = $db->tbl->product->select('product_id', 'title', 'brand_id')->limit(3);

$assocRows = $query->rows;
$numRows = $query->rows(Db::FETCH_NUM);

$decoratedRows = $query->rows(decorator: function (&$row, &$key) {
    $key = $row['product_id'] . '-' . $row['brand_id'];
    $row = array_values($row);
});

$assoc = $query->rows(keyColumn: 'product_id', decorator: 'title');
$assoc = $query->assoc;

$assocRow = $query->row;
$bothRow = $query->row(Db::FETCH_NUM | Db::FETCH_ASSOC);

$productId = $query->column;
$title = $query->column(1);
```

### Columns Clause

Method `select(...)` (see above) returns [`SelectQuery`](./Db/Sql/SelectQuery.php) object. You can change columns using methods `SelectQuery::columns()` or `SelectQuery::addColumns()` ([select.php](https://github.com/phpvv/db-examples/blob/master/examples/select/03.select.php#L23-L28)):

```php
$query = $db->tbl->product->select()
    ->columns('product_id', 'brand_id')
    ->addColumns('title', 'price');
```

All these methods accepts `string` or [`Expression`](./Db/Sql/Expressions/Expression.php) interface as each column. So you can do something like this ([select.php](https://github.com/phpvv/db-examples/blob/master/examples/select/03.select.php#L31-L48)):

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
```

### From Clause

To set table or view to query you can call `from()` method or create query directly from [`Table`](./Db/Model/Table.php) or [`View`](./Db/Model/View.php) ([from.php](https://github.com/phpvv/db-examples/blob/master/examples/select/04.from.php#L22-L29)):
```php
$query = $db->tbl->product->select(/*...*/);
// or
$query = $db->select(/*...*/)->from($db->tbl->product); // same as above
// or
$query = $db->select(/*...*/)->from('tbl_product'); // not same as above regarding JOIN clause
```

By default, alias of table (or view) consists of first letters of each word of table (or view) name without prefix (`tbl_`,`t_`, `vw_`, `v_`). For example: `tbl_order` -> `o`, `tbl_order_item` -> `oi`.

To change table alias, call `mainTableAs()` method of query ([from.php](https://github.com/phpvv/db-examples/blob/master/examples/select/04.from.php#L32-L33)):
```php
$query = $db->tbl->product->select(/*...*/)->mainTableAs('prod');
```

### Join Clause

To set JOIN clause use these methods: `join()`, `left()`, `right()`, `full()`.

Example ([join.php](https://github.com/phpvv/db-examples/blob/master/examples/select/05.join.php#L23-L28)):
```php
$query = $db->tbl->orderItem->select(/*...*/)  // SELECT ... FROM "tbl_order_item" "oi"
    ->join($db->tbl->order)                    // JOIN "tbl_order" "o" ON "o"."order_id" = "oi"."order_id"
    ->left($db->tbl->orderState)               // LEFT JOIN "tbl_order_state" "os" ON "os"."state_id" = "o"."state_id"
    ->join($db->tbl->product, 'oi')            // JOIN "tbl_product" "p" ON "p"."product_id" = "oi"."product_id"
    ->where('o.state_id', 1);                  // WHERE "o"."state_id" = ?
```

By default, table joins to previous table by primary key column. Default alias of table is first letters of each word of table name. You can change ON condition (second parameter) and alias (third parameter) ([join.php](https://github.com/phpvv/db-examples/blob/master/examples/select/05.join.php#L31-L43)):
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

Specify alias of table to which join is needed ([join.php](https://github.com/phpvv/db-examples/blob/master/examples/select/05.join.php#L47-L49)):
```php
$query = $db->tbl->orderItem->select(/*...*/)
    ->join($db->tbl->order)
    ->join($db->tbl->product, 'oi'); // join to tbl_order_item (not tbl_order) by product_id field
```

Specify column of table to which join is needed ([join.php](https://github.com/phpvv/db-examples/blob/master/examples/select/05.join.php#L53-L54)):
```php
$query = $db->tbl->orderItem->select(/*...*/)
    ->join($db->tbl->order, '.foo_id'); // "o"."order_id" = "oi"."foo_id"
```

Specify alias and column of table to which join is needed ([join.php](https://github.com/phpvv/db-examples/blob/master/examples/select/05.join.php#L58-L60)):
```php
$query = $db->tbl->orderItem->select(/*...*/)
    ->join($db->tbl->order)
    ->join($db->tbl->product, 'oi.foo_id'); // "p"."product_id" = "oi"."foo_id"
```

`joinParent()` ([join.php](https://github.com/phpvv/db-examples/blob/master/examples/select/05.join.php#L64-L65)):
```php
$query = $db->tbl->productCategory->select(/*...*/)
    //->joinParent('pc2', 'pc', 'parent_id') // same as below
    ->joinParent('pc2'); // JOIN "tbl_product_category" "pc2" ON ("pc2"."category_id" = "pc"."parent_id")
```
`joinBack()` ([join.php](https://github.com/phpvv/db-examples/blob/master/examples/select/05.join.php#L69-L70)):
```php
$query = $db->tbl->order->select(/*...*/)
    ->joinBack($db->tbl->orderItem); // JOIN "tbl_order_item" "oi" ON ("oi"."item_id" = "o"."order_id")
```

### Nested Columns

Nest resulting columns manually ([nested-columns.php](https://github.com/phpvv/db-examples/blob/master/examples/select/06.nested-columns.php#L22-L29)):
```php
$query = $db->tbl->product->select('product_id', 'price', 'weight')
    ->addNestedColumns('brand', 'b.brand_id', 'b.title') // first argument is nesting path: string|string[]
    ->addNestedColumns(['nested', 'color'], 'c.color_id', 'c.title')
    ->addNestedColumns(['nested', 'size'], 'width', 'height', 'depth')
    ->join($db->tbl->brand)
    ->join($db->tbl->color, 'p');

print_r($query->row);
```
Result:
```
Array
(
    [product_id] => 10
    [price] => 1000.00
    [weight] => 10000
    [brand] => Array
        (
            [brand_id] => 1
            [title] => Brand 1
        )

    [nested] => Array
        (
            [color] => Array
                (
                    [color_id] => 1
                    [title] => Black
                )

            [size] => Array
                (
                    [width] => 250.0
                    [height] => 500.0
                    [depth] => 500.0
                )

        )

)
```

Nest resulting columns with join ([nested-columns.php](https://github.com/phpvv/db-examples/blob/master/examples/select/06.nested-columns.php#L33-L50)):
```php
$query = $db->tbl->orderItem->select('item_id', 'price', 'quantity')
    ->joinNestedColumns(
        $db->tbl->order->select('order_id', 'amount', 'comment'), // sub query
        'oi.order_id',                                            // ON condition (see above)
        ['my', 'nested', 'order']                                 // nesting path
    )
    ->joinNestedColumns(
        $db->tbl->product->select('product_id', 'title')          // sub query
            ->joinNestedColumns(
                $db->tbl->brand->select('brand_id', 'title'),     // sub sub query
                'p.brand_id'
            )
            ->joinNestedColumns($db->tbl->color, 'p', 'color'),   // just join table - select all its columns
        'oi.product_id',
        'product'
    );

print_r($query->row);
```
Result:
```
Array
(
    [item_id] => 1
    [price] => 1000.00
    [quantity] => 1
    [my] => Array
        (
            [nested] => Array
                (
                    [order] => Array
                        (
                            [order_id] => 1
                            [amount] => 1133.47
                            [comment] =>
                        )

                )

        )

    [product] => Array
        (
            [brand_id] => Array
                (
                    [brand_id] => 1
                    [title] => Brand 1
                )

            [color] => Array
                (
                    [color_id] => 1
                    [title] => Black
                )

            [product_id] => 10
            [title] => Computer 10
        )

)
```

### Where Clause

To set query condition use `where()` method. Each `where()` adds `AND` condition.
Method accepts:
- `Condition` as first argument ([where.php](https://github.com/phpvv/db-examples/blob/master/examples/select/07.where.php#L23-L35)):
```php
$query = $db->tbl->product->select(/*...*/)
    ->where(                                // WHERE
        Sql::condition()
            ->and('color_id')->eq(5)            // ("color_id" = ?
            ->and('price')->lte(2000)           // AND "price" <= ?
            ->and('title')->isNotNull()         // AND "title" IS NOT NULL
            ->and('brand_id')->in(2, 3)         // AND "brand_id" IN (?, ?)
            ->and('width')->between(250, 350)   // AND "width" BETWEEN ? AND ?
            ->and('state=1')->custom()          // AND state=1) -- w/o quotes: custom query not changed
    );

```
- `Expression|string` as first argument and (binding) value (or `Expression`) to compare as second argument ([where.php](https://github.com/phpvv/db-examples/blob/master/examples/select/07.where.php#L37-L46)):
```php
$query = $db->tbl->product->select(/*...*/)
    ->where('color_id', 5)      // same: `->where('color_id =', 5)`
    ->where(
        'price <=', // supported operators: = | != | <> | < | > | <= | >=
        $db->tbl->product->select('AVG(price)') // HEAVING clause described below
    )
    ->where('title !=', null);
```
- `string` as custom SQL as first argument and (binding) array of values as second argument ([where.php](https://github.com/phpvv/db-examples/blob/master/examples/select/07.where.php#L49-L54)):
```php
$query = $db->tbl->product->select(/*...*/)
    ->where('`width` BETWEEN ? AND ?', [250, 350])  // custom sql with binding parameters
    ->where('state=1');                             // custom sql w/o binding parameters
```
- array as first argument ($expression => $parameter) ([where.php](https://github.com/phpvv/db-examples/blob/master/examples/select/07.where.php#L57-L68)):
```php
$query = $db->tbl->product->select(/*...*/)
    ->where([
        'color_id' => 5,
        'price <=' => 2000,
        'title !=' => null,
        Sql::condition('brand_id')->eq(2)->or->eq(3),   // AND ("brand_id" = ? OR "brand_id" = ?)
        '`width` BETWEEN ? AND ?' => [250, 350],
        'state=1',
    ]);
```

#### Where shortcuts

Query has some shortcuts methods:
- `->whereId(1)` (for `$db->tbl->product->select()` - `product_id = ?`);
- `->where[Not]In('brand_id', 1, 2, 3)`;
- `->whereId[Not]In(1, 2, 3)`;
- `->where[Not]Between('width', 250, 350)`;
- `->where[Not]Like('title', 'computer%', caseInsensitive: true)`.

### GroupBy and Having Clauses

To set GROUP BY clause use `groupBy()` method that behaves like `columns()` (see [Columns Clause](#columns-clause)).

To set condition for aggregate use `having()` method that behaves like `where()` (see [Where Clause](#where-clause)).

Example ([group-by-having.php](https://github.com/phpvv/db-examples/blob/master/examples/select/08.group-by-having.php#L22-L28)):
```php
$query = $db->tbl->product->select('b.title brand', 'COUNT(*) cnt')
    ->join($db->tbl->brand)
    ->groupBy('b.title')
    ->having('COUNT(*) > ', 1);
```

### OrderBy Clause

Simple order by columns ([order-by.php](https://github.com/phpvv/db-examples/blob/master/examples/select/09.order-by.php#L23-L31)):
```php
$query = $db->tbl->product->select('b.title brand', 'p.title product', 'price')
    ->left($db->tbl->brand)
    ->orderBy(      // ORDER BY
        'b.title',  //     "b"."title" NULLS FIRST,
        '-price'    //     "price" DESC NULLS LAST
    );
```

Order by expression (CASE for example) ([order-by.php](https://github.com/phpvv/db-examples/blob/master/examples/select/09.order-by.php#L33-L42)):
```php
$query = $db->tbl->product->select('p.title product', 'color_id')
    ->orderBy(                  // ORDER BY
        Sql::case('color_id')   //     CASE "color_id"
            ->when(5)->then(1)  //         WHEN 5 THEN 1
            ->when(1)->then(2)  //         WHEN 1 THEN 2
            ->else(100)         //         ELSE 100 END
    );
```

### Limit Clause

Use `->limit($count, $offset)` ([limit.php](https://github.com/phpvv/db-examples/blob/master/examples/select/10.limit.php#L21-L24)):
```php
$query = $db->tbl->product->select()->orderBy('product_id')->limit(3, 2);
```

## Insert

### Create [`InsertQuery`](./Db/Sql/InsertQuery.php)

There are several variants to create `InsertQuery` ([create-insert-query.php](https://github.com/phpvv/db-examples/blob/master/examples/insert/01.create-insert-query.php#L24-L35)):

```php
use App\Db\MainDb;

$db = MainDb::instance();
$connection = $db->getConnection(); // or $db->getFreeConnection();
// $connection = new \VV\Db\Connection(...);

// from Connection directly:
$insertQuery = $connection->insert()->into('tbl_order');
// from Db:
$insertQuery = $db->insert()->into('tbl_order');
// from Table (recommended):
$insertQuery = $db->tbl->order->insert();
// $insertQuery = $connection->insert()->from($db->tbl->order);
```

Last variant is preferable due to adjusting type of inserted value to column type:
```php
$db->tbl->foo->insert([
    'int_column' => true, // inserts `1` (or `0` for `false`)
    'bool_column' => 1, // inserts `true` for `1` and `false` for `0` (exception for other numbers)
    'date_column' => new \DateTimeImmutable(), // inserts date only: `Y-m-d`
    'time_column' => new \DateTimeImmutable(), // inserts time only: `H:i:s`
    'timestamp_column' => new \DateTimeImmutable(), // inserts date and time: `Y-m-d H:i:s`
    'timestamp_tz_column' => new \DateTimeImmutable(), // inserts date and time with time zone: `Y-m-d H:i:s P`
]);
```

### Execute [`InsertQuery`](./Db/Sql/InsertQuery.php)

Just execute:
```php
$result = $query->exec();
```

Get inserted ID (autoincrement) or affected rows ([execute-insert-query.php](https://github.com/phpvv/db-examples/blob/master/examples/insert/02.execute-insert-query.php#L28-L32)):
```php
$result = $query->initInsertedId()->exec();
$id = $result->insertedId();
$affectedRows = $result->affectedRows();
```

Execute query and return inserted ID or affected rows ([execute-insert-query.php](https://github.com/phpvv/db-examples/blob/master/examples/insert/02.execute-insert-query.php#L35-L38)):
```php
$id = $query->insertedId();             // executes Insert
$affectedRows = $query->affectedRows(); // executes Insert too
```

### Insert single row

Regular insert query ([insert-single-row.php](https://github.com/phpvv/db-examples/blob/master/examples/insert/03.insert-single-row.php#L22-L27)):
```php
$query = $db->tbl->order->insert()
    ->columns('user_id', 'comment')
    ->values(1, 'my comment');
```

Insert assignment list ([insert-single-row.php](https://github.com/phpvv/db-examples/blob/master/examples/insert/03.insert-single-row.php#L29-L38)):
```php
$query = $db->tbl->order->insert()
    // ->set([
    //     'user_id' => 1,
    //     'comment' => 'my comment',
    // ])
    ->set('user_id', 1)
    ->set('comment', 'my comment');
```

Shortcut ([insert-single-row.php](https://github.com/phpvv/db-examples/blob/master/examples/insert/03.insert-single-row.php#L40-L44)):
```php
$insertedId = $db->tbl->order->insert([
    'user_id' => 1,
    'date_created' => new \DateTime(),
]);
```

### Insert multiple rows

Insert values list ([insert-multiple-rows.php](https://github.com/phpvv/db-examples/blob/master/examples/insert/04.insert-multiple-rows.php#L33-L46)):
```php
$query = $db->tbl->orderItem->insert()->columns('order_id', 'product_id', 'price', 'quantity');
foreach ($valuesList as $values) {
    $query->values(...$values);
}
```

Insert from Select ([insert-multiple-rows.php](https://github.com/phpvv/db-examples/blob/master/examples/insert/04.insert-multiple-rows.php#L52-L67)):
```php
// copy order
$newOrderId = $db->tbl->order->insert([
    'user_id' => $userId,
    'date_created' => new \DateTime(),
]);

$query = $db->tbl->orderItem->insert()
    ->columns('order_id', 'product_id', 'price', 'quantity')
    ->values(
        $db->tbl->orderItem
            ->select((string)$newOrderId, 'product_id', 'price', 'quantity')
            ->where('order_id', $orderId)
    );
```

Insert values list executing statement per N rows ([insert-multiple-rows.php](https://github.com/phpvv/db-examples/blob/master/examples/insert/04.insert-multiple-rows.php#L71-L78)):
```php
$query = $db->tbl->orderItem->insert()
    ->columns('order_id', 'product_id', 'price', 'quantity')
    ->execPer(1000);
foreach ($valuesList as $values) {
    $query->values(...$values);
}
$query->execPerFinish(); // exec last
```

## Update

*Coming soon...*

## Delete

*Coming soon...*

## Transaction

*Coming soon...*

## Condition

*Coming soon...*

## Case Expression

*Coming soon...*
