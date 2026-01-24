<?php echo '<?xml version="1.0" encoding="utf-8"?>'; ?>
<rss version="2.0"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
    xmlns:admin="http://webns.net/mvcb/"
    xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
    xmlns:content="http://purl.org/rss/1.0/modules/content/">

    <channel>

    <title>{{ $feed_name }}</title>

    <link>{{ $feed_url }}</link>
    <description>{{ $page_description }}</description>
    <dc:language>{{ $page_language }}</dc:language>
    <dc:creator>{{ $creator_email }}</dc:creator>

    <dc:rights>Copyright {{ date('Y') }}</dc:rights>
    <admin:generatorAgent rdf:resource="https://laravel.com/" />

    @foreach($news as $entry)
        <item>

          <title>{{ htmlspecialchars($entry->title, ENT_XML1, 'UTF-8') }}</title>
          <link>{{ url('/news') }}</link>
          <guid>{{ url('/news') }}#{{ $entry->id }}</guid>

          <description><![CDATA[
      {!! $entry->body !!}
      ]]></description>
      <pubDate>{{ $entry->posted->format('r') }}</pubDate>
        </item>

    @endforeach

    </channel>
</rss>
