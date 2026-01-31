<?php echo '<?xml version="1.0" encoding="utf-8"?>'; ?>

<collection user="{{ $username }}">
@foreach($records as $record)
    <record>
        <artist>{{ htmlspecialchars($record->artist_name, ENT_XML1, 'UTF-8') }}</artist>
        <title>{{ htmlspecialchars($record->title, ENT_XML1, 'UTF-8') }}</title>
        <year>{{ htmlspecialchars($record->year ?? '', ENT_XML1, 'UTF-8') }}</year>
        <format>{{ htmlspecialchars($record->format ?? '', ENT_XML1, 'UTF-8') }}</format>
    </record>
@endforeach
</collection>
