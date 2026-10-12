<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;

class DailySalesReport extends Model
{
    protected $table = 'sales_details';
    
    protected $primaryKey = 'report_date';
    
    public $incrementing = false;
    
    protected $keyType = 'string';
    
    public $timestamps = false;
    
    protected $guarded = [];

    public function newEloquentBuilder($query): Builder
    {
        $query = parent::newEloquentBuilder($query);
        
        return new class($query->getQuery()) extends Builder {
            public function getCountForPagination($columns = ['*'])
            {
                $this->getQuery()->orders = [];
                $sql = $this->toSql();
                $bindings = $this->getBindings();
                
                return $this->getModel()->getConnection()
                    ->table(new Expression("($sql) as sub"))
                    ->setBindings($bindings)
                    ->count();
            }
        };
    }
}