<?php
/*
	Question2Answer Edit History plugin
	License: http://www.gnu.org/licenses/gpl.html
*/


// Usage: $html = diff_string::compare( $original, $new )
class diff_string
{
	// ($o,$n) are strings
	public static function compare( $o, $n )
	{
		$str = '';
		$o = trim($o);
		$n = trim($n);

		$oArr = preg_split( '/\s+/', $o );
		$nArr = preg_split( '/\s+/', $n );
		list( $oOut, $nOut ) = self::diff( $oArr, $nArr );

		// arrays to preserve whitespace
		preg_match_all( '/\s+/', $o, $matches );
		$oWS = $matches[0];
		$oWS[] = '';
		preg_match_all( '/\s+/', $n, $matches );
		$nWS = $matches[0];
		$nWS[] = '';

		if ( count($nOut) == 0 )
		{
			for ( $i = 0; $i < count($oOut); $i++ )
				$str .= '<del>' . $oOut[$i] . $oWS[$i] . '</del>';
		}
		else
		{
			if ( !is_array($nOut[0]) )
			{
				for ( $j = 0; $j < count($oOut) && !is_array($oOut[$j]); $j++ )
					$str .= '<del>' . $oOut[$j] . $oWS[$j] . '</del>';
			}

			for ( $i = 0; $i < count($nOut); $i++ )
			{
				if ( !is_array($nOut[$i]) )
					$str .= '<ins>' . $nOut[$i] . $nWS[$i] . '</ins>';
				else
				{
					$pre = '';
					for ( $j = $nOut[$i]['row']+1; $j < count($oOut) && !is_array($oOut[$j]); $j++ )
						$pre .= '<del>' . $oOut[$j] . $oWS[$j] . '</del>';
					$str .= ' ' . $nOut[$i]['text'] . $nWS[$i] . $pre;
				}
			}
		}

		return $str;
	}

	// ($o,$n) are arrays of words
	private static function diff( $o, $n )
	{
		$ns = array();
		$os = array();

		for ( $i = 0; $i < count($n); $i++ )
		{
			$word = $n[$i];
			if ( !isset( $ns[$word] ) )
				$ns[$word] = array( 'rows' => array(), 'o' => null );
			$ns[$word]['rows'][] = $i;
		}

		for ( $i = 0; $i < count($o); $i++ )
		{
			$word = $o[$i];
			if ( !isset( $os[$word] ) )
				$os[$word] = array( 'rows' => array(), 'n' => null );
			$os[$word]['rows'][] = $i;
		}

		foreach ( $ns as $w=>$nsw )
		{
			if ( count( $ns[$w]['rows'] ) == 1 && isset( $os[$w] ) && count( $os[$w]['rows'] ) == 1 )
			{
				$n[ $ns[$w]['rows'][0] ] = array( 'text' => $n[ $ns[$w]['rows'][0] ], 'row' => $os[$w]['rows'][0] );
				$o[ $os[$w]['rows'][0] ] = array( 'text' => $o[ $os[$w]['rows'][0] ], 'row' => $ns[$w]['rows'][0] );
			}
		}

		for ( $i = 0; $i < count($n)-1; $i++ )
		{
			$word = $n[$i];
			if ( is_array( $word ) && !is_array( $n[$i+1] ) && $word['row'] + 1 < count($o) &&
				!is_array( $o[ $word['row']+1 ] ) && $n[$i+1] == $o[ $word['row']+1 ] )
			{
				$n[$i+1] = array( 'text' => $n[$i+1], 'row' => $word['row']+1 );
				$o[ $word['row']+1 ] = array( 'text' => $o[ $word['row']+1 ], 'row' => $i+1 );
			}
		}

		for ( $i = count($n)-1; $i > 0; $i-- )
		{
			$word = $n[$i];
			if ( is_array( $word ) && !is_array( $n[$i-1] ) && $word['row'] > 0 &&
				!is_array( $o[ $word['row']-1 ] ) && $n[$i-1] == $o[ $word['row']-1 ] )
			{
				$n[$i-1] = array( 'text' => $n[$i-1], 'row' => $word['row']-1 );
				$o[ $word['row']-1 ] = array( 'text' => $o[ $word['row']-1 ], 'row' => $i-1 );
			}
		}

		return array( $o, $n );
	}

}
