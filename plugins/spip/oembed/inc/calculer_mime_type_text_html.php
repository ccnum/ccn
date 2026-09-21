<?php

function inc_calculer_mime_type_text_html_dist(int $id_document, string $extension, string $mime_type): string {
    $oembed = sql_getfetsel('oembed', 'spip_documents', "id_document=$id_document");
    return $oembed ? 'text/oembed' : $mime_type;
}