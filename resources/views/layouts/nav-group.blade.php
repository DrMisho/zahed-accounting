<div class="relative group">
    <button type="button" class="px-3 py-2 rounded-lg {{ $active ? 'bg-brand-900 text-gold-300 font-bold' : 'text-slate-600 hover:bg-brand-50 hover:text-brand-900' }}">
        {{ $label }}
    </button>
    <div class="hidden group-hover:block group-focus-within:block absolute right-0 top-full pt-1 z-40 min-w-[220px]">
        <div class="bg-white border border-slate-200 rounded-xl py-1 text-sm">
            @foreach($links as $link)
                @php
                    $name = $link[0];
                    $text = $link[1];
                    $params = $link[2] ?? [];
                @endphp
                <a href="{{ route($name, $params) }}" class="block px-3 py-2 text-slate-700 hover:bg-gold-50 hover:text-brand-900">{{ $text }}</a>
            @endforeach
        </div>
    </div>
</div>
