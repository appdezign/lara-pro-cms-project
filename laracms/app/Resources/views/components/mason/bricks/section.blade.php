@props([
    'text' => null,
    'image' => null,
    'image_position' => null,
])

<section class="mason-section py-12">
	<div class="container-fluid">
		<div class="row">
			<div
					@class([
						'col-sm-6',
						'order-0' => $image_position === 'start',
						'order-1' => $image_position === 'end',
					])
			>
				@if (filled($image))
					<div class="h-100 ratio ratio-16x9" style="background-repeat:none; background-size: cover; background-position: 50% 50%; background-image: url({{ \Illuminate\Support\Facades\Storage::url($image) }})">
					</div>
				@endif
			</div>
			<div class="col-sm-6">
				@if ($text)
					{!! $text !!}
				@endif
			</div>
		</div>
	</div>
</section>
