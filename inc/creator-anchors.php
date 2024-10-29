<?php

/**
 * Anchors Creator.
 */
class Anchors_Creator
{
    public function __construct()
    {
        if( get_option( 'addiction_recovery_anchors_creator_enabled' ) != null ) {
            add_filter( 'the_content', array( $this, 'parse_content_and_add_nachors_list' ) );
        }
    }

    /**
     * Return string with changed heading and a new list with anchors' links.
     *
     * @param string $text
     * @return string
     */
    public function parse_content_and_add_nachors_list( string $text ): string
    {
        $html_list = '';
        
        if( is_single() ) {
            if( ! empty( $text ) ) {
                $headings = array();
                $anchors_new = array();
                $html_list = '';
                // \\A-Za-z0-9\-\.\,\!\?\s\)\(\*\:\;\$\#\@
                if( preg_match_all( '/<h2>[A-Za-z0-9\s\/\\\:\)\(\*\-\/\.\,\!\?]*<\/h2>/', $text, $matches ) ) {
                    $headings = $matches[0];
                }

                if( ! empty( $headings ) ) {
                    $html_list .= '<h2>Table of Contents</h2>';
                    $html_list .= '<ul>';
                    foreach( $headings as $key => $heading ) {
                        if( ! empty( $heading ) ) {
                            
                            $key = $key + 1;
                            $new_heading = str_replace( '<h2>', "<h2 id=\"anchor{$key}\">", $heading );

                            $text = str_replace( $heading, $new_heading, $text );
                            $content = explode( '>', $heading )[1];
                            $content = str_replace( '</h2', '', $content );
                            $html_list .= "<li><a href=\"#anchor{$key}\" nofollow>{$content}</a></li>";
                        }
                    }
                    $html_list .= '</ul>';
                }
                
            }
        }
        
        return $html_list . $text;
    }
}

new Anchors_Creator();
