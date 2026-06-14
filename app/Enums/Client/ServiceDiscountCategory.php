<?php
namespace App\Enums\Client;
enum ServiceDiscountCategory:int{
    case DISCOUNT=0;
    case TAX=1;

  public function values()
  {
      return array_column(self::cases(),'value');
  }

}
?>
