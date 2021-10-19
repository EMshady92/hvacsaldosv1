<?php

namespace App\Http\Livewire;

use App\Models\Brand;
use Livewire\Component;

use Livewire\WithPagination;

use App\Models\Product;
use App\Models\Subcategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CategoryFilter extends Component
{
    use WithPagination;

    public $category, $subcategoria, $marca, $country;

    public $view = "grid";


    protected $queryString = ['subcategoria', 'marca'];

    public function limpiar()
    {
        $this->reset(['subcategoria', 'marca', 'page']);
    }


    public function updatedSubcategoria()
    {
        $this->resetPage();
    }

    public function updatedMarca()
    {
        $this->resetPage();
    }

    public function render()
    {
        $countries['MX'] = 1;
        $countries['COL'] = 2;
        $countries['PER'] = 3;
        $countries['EC'] = 4;
        $countries['CR'] = 5;
        $countries['CL'] = 6;

        $country_id = array_key_exists($this->country, $countries)  ? $countries[$this->country] : 'MX';


        $productsQuery = Product::join('states', 'states.id', '=', 'state_id')
            ->select('products.*', 'states.country_id')
            ->where('category_id', $this->category->id)
            ->where('country_id', $country_id);

        if ($this->subcategoria) {
            $productsQuery = $productsQuery->whereHas('subcategory', function (Builder $query) {
                $query->where('slug', $this->subcategoria);
            });
        }

        if ($this->marca) {
            $productsQuery = $productsQuery->whereHas('brand', function (Builder $query) {
                $query->where('name', $this->marca);
            });
        }

        $products = $productsQuery->paginate(20);
        $subcategories = Subcategory::all();
        $brands = Brand::all();

        return view('livewire.category-filter', compact('products', 'subcategories', 'brands'));
    }
}
