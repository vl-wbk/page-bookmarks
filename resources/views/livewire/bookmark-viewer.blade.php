<div class="flex">
    <x-filament::icon-button
        icon="{{ $this->getIcons()['view_bookmarks'] }}"
        color="gray"
        x-on:click="$dispatch('open-modal', { id: 'bookmark-items-modal' })"
    />

    <x-filament::modal
        id="bookmark-items-modal"
        width="md"
        :heading="__('page-bookmarks::translation.my_bookmarks')"
        :slide-over="config('page-bookmarks.modal.view_bookmarks') === 'slideOver' ? true : false"
        x-on:open-modal.window="if ($event.detail.id === 'bookmark-items-modal') $wire.$refresh()"
        x-on:refreshBookmarks.window="$wire.$refresh()"
    >
        <div class="px-2 mb-4">
            <div class="relative">
                <x-filament::input.wrapper
                    :prefix-icon="$this->getIcons()['search']"
                >
                    <x-filament::input
                        type="search"
                        placeholder="{{ __('page-bookmarks::translation.search_bookmarks') }}"
                        icon="{{ $this->getIcons()['search'] }}"
                        x-data
                        x-on:input.debounce.15ms="
                            const searchTerm = $event.target.value.toLowerCase();
                            const bookmarkItems = document.querySelectorAll('[data-bookmark-item]');
                            const folderVisibleCount = {};

                            // First check all bookmark items
                            bookmarkItems.forEach(item => {
                                const name = item.getAttribute('data-bookmark-name').toLowerCase();
                                const folder = item.getAttribute('data-bookmark-folder');

                                // Determine if the bookmark itself is visible
                                const isVisible = name.includes(searchTerm);
                                item.style.display = isVisible ? 'flex' : 'none';

                                // Count visible items
                                if (isVisible) {
                                    folderVisibleCount[folder] = (folderVisibleCount[folder] || 0) + 1;
                                }
                            });

                            // Then show/hide folder containers (and handle folder name match)
                            document.querySelectorAll('[data-folder-container]').forEach(folder => {
                                const folderName = folder.getAttribute('data-folder-name');
                                const lowerCaseFolderName = folderName.toLowerCase();
                                const counter = folder.querySelector('[data-folder-counter]');

                                const visibleCount = folderVisibleCount[folderName] || 0;

                                // Check if the folder name itself matches the search term
                                const folderNameMatches = lowerCaseFolderName.includes(searchTerm);

                                // The folder should be visible if:
                                // a) It has at least one matching bookmark (visibleCount > 0) OR
                                // b) Its name matches the search term (folderNameMatches)
                                const shouldBeVisible = visibleCount > 0 || folderNameMatches;

                                // If the folder name matches, we need to show ALL items in that folder
                                if (folderNameMatches) {
                                    document.querySelectorAll(`[data-bookmark-folder='${folderName}']`).forEach(item => {
                                        item.style.display = 'flex';
                                    });
                                    // Update the count to reflect all items are visible for a folder name match
                                    if (visibleCount === 0) {
                                        folderVisibleCount[folderName] = document.querySelectorAll(`[data-bookmark-folder='${folderName}']`).length;
                                    }
                                }

                                if (counter) {
                                    // Set the badge counter to the actual visible count (or total count if folder name matches)
                                    counter.textContent = folderVisibleCount[folderName] || 0;
                                }

                                folder.style.display = shouldBeVisible ? 'block' : 'none';
                            });
                        "
                    />
                </x-filament::input.wrapper>
            </div>
        </div>

        <div
            class="px-2 -mx-2"
            wire:key="bookmarks-list"
        >
            @forelse($this->bookmarksByFolder as $folder => $bookmarks)
                @php
                    $folderArray = json_decode($folder, true);
                    $folderName = $folderArray['name'];
                    $folderId = $folderArray['id'];
                @endphp

                <div
                    wire:key="folder-{{ $loop->index }}"
                    x-data="{ open: true }"
                    class="mb-3"
                    data-folder-container
                    data-folder-name="{{ $folderName }}"
                >
                    <div
                        class="flex items-center justify-between group px-2 py-2 text-sm font-medium rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700"
                        @click="open = !open"
                    >
                        <div class="flex items-center">
                            <x-filament::icon
                                icon="{{ $this->getIcons()['folder'] }}"
                                class="w-5 h-5 mr-2 text-gray-400 shrink-0 dark:text-gray-500"
                            />
                            <span class="text-gray-700 dark:text-gray-300">
                                {{ ucfirst($folderName) }}
                            </span>
                        </div>
                        <div class="flex items-center">
                            @if($folderId)
                                <div class="transition duration-300 opacity-0 group-hover:opacity-100">
                                    <x-filament::icon-button
                                        wire:key="delete-folder-{{ $folderId }}"
                                        icon="{{ $this->getIcons()['delete'] }}"
                                        color="danger"
                                        size="xs"
                                        x-on:click.stop="$wire.mountAction('deleteFolder', {id: {{ $folderId }}})"
                                        class="ml-auto mr-2"
                                    />
                                </div>
                            @endif
                            {{-- **FIX 2: Added data-folder-counter attribute to the badge** --}}
                            <x-filament::badge color="gray" data-folder-counter>{{ count($bookmarks) }}</x-filament::badge>

                            <x-filament::icon
                                icon="{{ $this->getIcons()['chevron_down'] }}"
                                class="w-4 h-4 text-gray-400 transition-transform duration-300"
                                x-bind:class="{ 'rotate-0': open, '-rotate-90': !open }"
                            />
                        </div>
                    </div>

                    <div
                        class="mt-1 space-y-1"
                        x-show="open"
                        x-collapse
                    >
                        <div class="pl-4 ml-3 border-l border-gray-200 dark:border-gray-700">
                            @foreach ($bookmarks as $bookmark)
                                <div
                                    class="relative flex items-center justify-between px-2 py-1.5 mb-1 text-gray-600 group dark:text-gray-400 hover:bg-primary-500/10 hover:text-primary-600 dark:hover:text-primary-500 rounded-md transition duration-150"
                                    wire:key="bookmark-{{ $bookmark->id }}"
                                    data-bookmark-item
                                    data-bookmark-name="{{ $bookmark->name }}"
                                    {{-- **FIX 3: Changed data-bookmark-folder to use the clean folder name** --}}
                                    data-bookmark-folder="{{ $folderName }}"
                                >
                                    <a
                                        wire:navigate
                                        wire:click="$dispatch('close-modal', { id: 'bookmark-items-modal' })"
                                        href="{{ $bookmark->url }}"
                                        class="flex items-center w-full truncate"
                                        title="{{ $bookmark->name }}"
                                    >
                                        <x-filament::icon
                                            icon="{{ $this->getIcons()['bookmark_item'] }}"
                                            class="w-4 h-4 mr-2 text-gray-400 shrink-0 dark:text-gray-500"
                                        />
                                        <span class="text-sm truncate">{{ $bookmark->name }}</span>
                                    </a>

                                    <div class="transition duration-300 opacity-0 group-hover:opacity-100">
                                        <x-filament::icon-button
                                            icon="{{ $this->getIcons()['delete'] }}"
                                            color="danger"
                                            size="xs"
                                            wire:click.stop="deleteBookmark({{ $bookmark->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="deleteBookmark({{ $bookmark->id }})"
                                            class="ml-auto"
                                        />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @empty
                <x-filament::empty-state
                    icon="heroicon-o-bookmark-slash"
                >
                    <x-slot name="heading">
                        {{ __('page-bookmarks::translation.no_bookmarks_found') }}
                    </x-slot>

                    <x-slot name="description">
                        {{ __('page-bookmarks::translation.click_the_bookmark_plus_icon_to_save_your_first_bookmark') }}.
                    </x-slot>
                </x-filament::empty-state>
            @endforelse
        </div>
    </x-filament::modal>

    <x-filament-actions::modals />
</div>
