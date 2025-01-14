<div wire:init="loadPosts" class="categories-posts">
    @if (count($products))
        <div class="glider-contain">
            <ul class="glider-{{ $category->id }}">

                @foreach ($products as $product)

                    <li class="bg-white rounded-lg shadow {{ $loop->last ? '' : 'sm:mr-4' }} border-2">
                        <a href="{{ route('products.show', $product) }}">
                            <article>
                                <figure class="border-b-4 border-orange-500">
                                    <img class="h-48 w-full object-cover object-center"
                                        src="{{ count($product->images) ? asset($product->images->first()->url) : asset('/images/image-not-found.png') }}"
                                        alt="">
                                </figure>

                                <div class="py-4 px-4">
                                    <h1 class="text-lg font-semibold">
                                        @if ($product->isOffer)
                                            <i class="fas fa-fire-alt text-red-500"></i>
                                        @endif
                                        {{ Str::limit($product->name, 15) }}
                                    </h1>
                                    <del class="text-red-800 font-bold text-sm">
                                        {{ $product->currency ? $product->currency->currency : '' }}
                                        {{ $product->currency ? $product->currency->symbol : '$' }}{{ number_format($product->commercial_price, 0, '.', ',') }}
                                    </del>
                                    <p class="font-bold text-trueGray-700">
                                        {{ $product->currency ? $product->currency->currency : '' }}
                                        {{ $product->currency ? $product->currency->symbol : '$' }}{{ number_format($product->price, 0, '.', ',') }}
                                    </p>
                                </div>
                            </article>
                        </a>
                    </li>
                @endforeach
            </ul>

            <button aria-label="Previous" class="glider-prev">«</button>
            <button aria-label="Next" class="glider-next">»</button>
            <div role="tablist" class="dots"></div>
        </div>

    @else

        <span class="px-16 ml-6 text-xl">No hay articulos en esta sección</span>

    @endif
    @push('script')
        <script>
            $(window).on('load', function() {
                setTimeout(() => {
                    $('.animate-bounce').hide();
                    $('#main-content').removeClass('blur');
                }, 500);
            });
        </script>
    @endpush
</div>
