{{-- Reusable form partial for creating/editing plans --}}
@php
    $val = fn(string $field, $default = '') => old($field, optional($plan)->$field ?? $default);
@endphp

<div class="grid md:grid-cols-2 gap-4">
    <div>
        <label class="block text-xs text-gray-500 mb-1.5 font-medium">Plan Name <span class="text-red-400">*</span></label>
        <input type="text" name="name" value="{{ $val('name') }}" required
               class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white focus:outline-none focus:border-indigo-500 transition"
               placeholder="e.g. Pro">
        @error('name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-xs text-gray-500 mb-1.5 font-medium">Slug <span class="text-red-400">*</span></label>
        <input type="text" name="slug" value="{{ $val('slug') }}" required
               class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white focus:outline-none focus:border-indigo-500 transition"
               placeholder="e.g. pro">
        <p class="text-xs text-gray-600 mt-1">Lowercase letters, numbers, hyphens only</p>
        @error('slug') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-xs text-gray-500 mb-1.5 font-medium">Monthly Price (USD) <span class="text-red-400">*</span></label>
        <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm">$</span>
            <input type="number" name="price" value="{{ $val('price', '0') }}" step="0.01" min="0" required
                   class="w-full pl-7 pr-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white focus:outline-none focus:border-indigo-500 transition"
                   placeholder="0.00">
        </div>
        @error('price') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-xs text-gray-500 mb-1.5 font-medium">Credits / Month <span class="text-red-400">*</span></label>
        <input type="number" name="credits" value="{{ $val('credits', '0') }}" min="0" required
               class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white focus:outline-none focus:border-indigo-500 transition"
               placeholder="e.g. 500">
        @error('credits') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-xs text-gray-500 mb-1.5 font-medium">Color Theme</label>
        <select name="color" class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white focus:outline-none focus:border-indigo-500 transition">
            @foreach(['slate' => 'Slate (Free)', 'indigo' => 'Indigo (Pro)', 'purple' => 'Purple (Enterprise)', 'emerald' => 'Emerald', 'rose' => 'Rose'] as $key => $label)
            <option value="{{ $key }}" {{ $val('color', 'indigo') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-xs text-gray-500 mb-1.5 font-medium">Sort Order</label>
        <input type="number" name="sort_order" value="{{ $val('sort_order', '0') }}" min="0"
               class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white focus:outline-none focus:border-indigo-500 transition">
    </div>
</div>

<div>
    <label class="block text-xs text-gray-500 mb-1.5 font-medium">Description</label>
    <input type="text" name="description" value="{{ $val('description') }}"
           class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white focus:outline-none focus:border-indigo-500 transition"
           placeholder="Short description for users">
    @error('description') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
</div>

<div>
    <label class="block text-xs text-gray-500 mb-1.5 font-medium">Features</label>
    <textarea name="features" rows="5"
              class="w-full px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm text-white font-mono focus:outline-none focus:border-indigo-500 transition resize-none"
              placeholder="One feature per line&#10;e.g.&#10;Mind Map Generator&#10;500 credits / month&#10;Priority support">{{ old('features', $plan ? implode("\n", $plan->features ?? []) : '') }}</textarea>
    <p class="text-xs text-gray-600 mt-1">Enter one feature per line</p>
    @error('features') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
</div>

<div class="flex items-center gap-3">
    <label class="relative inline-flex items-center cursor-pointer">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" class="sr-only peer"
               {{ $val('is_active', true) ? 'checked' : '' }}>
        <div class="w-10 h-5 bg-gray-700 peer-checked:bg-indigo-600 rounded-full peer transition after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-5"></div>
    </label>
    <span class="text-sm text-gray-300">Plan is active (visible to users)</span>
</div>
