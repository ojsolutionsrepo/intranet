<div>
    @foreach ($roots as $root)
        @include('directory::partials.org-node', ['person' => $root, 'people' => $people, 'depth' => 0])
    @endforeach
</div>
