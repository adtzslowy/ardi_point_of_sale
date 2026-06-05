@props([
    'categories',
    'selected'    => null,
    'name'        => 'category_id',
    'placeholder' => '-- Pilih kategori --',
])

@php
    $selectedId   = old($name, $selected?->id ?? '');
    $selectedName = $categories->find($selectedId)?->name ?? '';
    $categoriesJson = $categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->toJson();
@endphp

<div
    x-data="{
        open:          false,
        search:        '',
        dropdownStyle: '',
        categories:    [],
        selected: {
            id:   '{{ addslashes($selectedId) }}',
            name: '{{ addslashes($selectedName) }}'
        },
        get filtered() {
            if (!this.search) return this.categories
            return this.categories.filter(c =>
                c.name.toLowerCase().includes(this.search.toLowerCase())
            )
        },
        toggle() {
            this.open = !this.open
            if (this.open) {
                this.$nextTick(() => {
                    const rect       = this.$refs.trigger.getBoundingClientRect()
                    const spaceBelow = window.innerHeight - rect.bottom
                    const spaceAbove = rect.top
                    const dropH      = 260
                    if (spaceBelow >= dropH || spaceBelow >= spaceAbove) {
                        this.dropdownStyle = 'position:fixed;top:' + (rect.bottom + 4) + 'px;left:' + rect.left + 'px;width:' + rect.width + 'px;z-index:9999'
                    } else {
                        this.dropdownStyle = 'position:fixed;bottom:' + (window.innerHeight - rect.top + 4) + 'px;left:' + rect.left + 'px;width:' + rect.width + 'px;z-index:9999'
                    }
                    this.$nextTick(() => this.$refs.searchInput?.focus())
                })
            }
        },
        select(cat) {
            this.selected = cat
            this.search   = ''
            this.open     = false
        },
        clear() {
            this.selected = { id: '', name: '' }
            this.search   = ''
        },
        addAndSelect(cat) {
            const item = { id: String(cat.id), name: cat.name }
            if (!this.categories.some(c => c.id === item.id)) {
                this.categories.push(item)
            }
            this.search = ''
            this.selected = item
        }
    }"
    x-init="categories = JSON.parse($el.dataset.categories).map(c => ({ id: String(c.id), name: c.name }))"
    data-categories="{{ $categoriesJson }}"
    @keydown.escape.window="open = false"
    @click.outside="open = false"
    @category-added.window="addAndSelect($event.detail)"
    class="relative"
>
    {{-- Hidden input --}}
    <input type="hidden" name="{{ $name }}" :value="selected.id">

    {{-- Trigger --}}
    <div
        x-ref="trigger"
        @click="toggle()"
        @keydown.enter.prevent="toggle()"
        @keydown.space.prevent="toggle()"
        role="button"
        tabindex="0"
        aria-haspopup="listbox"
        :aria-expanded="open"
        class="input flex items-center justify-between text-left w-full cursor-pointer"
        :class="!selected.id
            ? 'text-neutral-400 dark:text-neutral-500'
            : 'text-neutral-900 dark:text-neutral-100'"
    >
        <span x-text="selected.name || '{{ $placeholder }}'"></span>
        <div class="flex items-center gap-1 shrink-0 ml-2">
            <button
                x-show="selected.id"
                x-cloak
                type="button"
                @click.stop="clear()"
                class="text-neutral-400 hover:text-neutral-600
                       dark:hover:text-neutral-300 transition-colors"
            >
                <x-heroicon-s-x-mark class="w-3.5 h-3.5" />
            </button>
            <x-heroicon-s-chevron-up-down class="w-4 h-4 text-neutral-400" />
        </div>
    </div>

    {{-- Dropdown --}}
    <div
        x-cloak
        x-show="open"
        :style="dropdownStyle"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="bg-white dark:bg-neutral-800
               border border-neutral-200 dark:border-neutral-700
               rounded-xl overflow-hidden shadow-lg"
    >
        {{-- Search --}}
        <div x-show="categories.length > 0"
             class="p-2 border-b border-neutral-100 dark:border-neutral-700">
            <div class="relative">
                <x-heroicon-o-magnifying-glass
                    class="w-3.5 h-3.5 absolute left-2.5 top-1/2 -translate-y-1/2
                           text-neutral-400 pointer-events-none"
                />
                <input
                    type="text"
                    x-model="search"
                    x-ref="searchInput"
                    @keydown.escape="open = false"
                    placeholder="Cari kategori..."
                    class="w-full pl-8 pr-3 py-1.5 text-xs rounded-lg
                           border border-neutral-200 dark:border-neutral-700
                           bg-neutral-50 dark:bg-neutral-900
                           text-neutral-900 dark:text-neutral-100
                           placeholder:text-neutral-400 dark:placeholder:text-neutral-500
                           focus:outline-none focus:ring-2 focus:ring-primary-500/30
                           focus:border-primary-500 transition-all duration-150"
                >
            </div>
        </div>

        {{-- Options --}}
        <div class="max-h-52 overflow-y-auto py-1">

            <button
                type="button"
                x-show="categories.length > 0"
                @click="clear(); open = false"
                class="w-full flex items-center px-3 py-2 text-xs text-left
                       text-neutral-400 dark:text-neutral-500 italic
                       hover:bg-neutral-50 dark:hover:bg-neutral-700
                       transition-colors duration-100"
            >
                {{ $placeholder }}
            </button>

            <div x-show="categories.length > 0"
                 class="h-px bg-neutral-100 dark:bg-neutral-700 mx-2 mb-1"></div>

            <template x-for="cat in filtered" :key="cat.id">
                <button
                    type="button"
                    @click="select(cat)"
                    class="w-full flex items-center justify-between px-3 py-2.5
                           text-xs text-left transition-colors duration-100"
                    :class="selected.id === cat.id
                        ? 'bg-primary-50 dark:bg-primary-900/40 text-primary-800 dark:text-primary-200 font-medium'
                        : 'text-neutral-700 dark:text-neutral-200 hover:bg-neutral-50 dark:hover:bg-neutral-700'"
                >
                    <span x-text="cat.name"></span>
                    <x-heroicon-s-check
                        x-show="selected.id === cat.id"
                        x-cloak
                        class="w-3.5 h-3.5 text-primary-500 shrink-0"
                    />
                </button>
            </template>

            {{-- Empty: pencarian tidak ketemu (hanya saat ada kategori) --}}
            <div
                x-show="categories.length > 0 && filtered.length === 0"
                class="px-3 py-6 text-xs text-center text-neutral-400"
            >
                <x-heroicon-o-face-frown class="w-5 h-5 mx-auto mb-1.5 opacity-40" />
                Kategori tidak ditemukan
            </div>

            {{-- Empty: belum ada kategori untuk cabang ini --}}
            <div
                x-show="categories.length === 0"
                class="px-3 py-6 text-xs text-center text-neutral-400"
            >
                <x-heroicon-o-folder-open class="w-6 h-6 mx-auto mb-2 opacity-40" />
                <p class="text-neutral-500 dark:text-neutral-400">Belum ada kategori untuk cabang ini</p>
                <p class="text-[11px] mt-1 text-neutral-400">Kategori dikelola terpisah per cabang.</p>
            </div>
        </div>
    </div>
</div>
