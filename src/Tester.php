<?php namespace Maer\Router;

class Tester
{
    protected $tokens = [
        'all'      => '.*',
        'alphanum' => '[a-zA-Z0-9]+',
        'alpha'    => '[a-zA-Z]+',
        'num'      => '[\-]?[\d\,\.]+',
        'any'      => '[^\/]+',
    ];


    /**
     * Add a regex token
     *
     * @param string $name
     * @param string $pattern
     */
    public function addToken($name, $pattern)
    {
        $this->tokens[$name] = $pattern;
    }


    /**
     * Get all tokens
     *
     * @return array
     */
    public function getTokens()
    {
        return $this->tokens;
    }


    /**
     * Match a pattern with a string
     *
     * @param  string $pattern
     * @param  string $string
     *
     * @return array
     */
    public function match($pattern, $string)
    {
        $pattern = $this->regexifyPattern($pattern);
        preg_match($pattern, $string, $matches);
        return $matches;
    }


    /**
     * Replace placeholders to regular expressions
     *
     * @param  string $pattern
     *
     * @return string
     */
    protected function regexifyPattern($pattern)
    {
        preg_match_all('/(\/?)\(:([^)]*)\)(\??)/', $pattern, $regExPatterns, PREG_SET_ORDER, 0);

        $pattern = preg_quote($pattern, '/');

        foreach ($regExPatterns as $regExPattern) {
            if (!empty($regExPattern[2]) && key_exists($regExPattern[2], $this->tokens)) {
                $replacement = sprintf(
                    '(%s%s)%s',
                    empty($regExPattern[1]) ? '' : '\/',
                    $this->tokens[$regExPattern[2]],
                    $regExPattern[3]
                );

                $pattern = str_replace(preg_quote($regExPattern[0], '/'), $replacement, $pattern);
            }
        }

        $pattern = str_replace('\?', '?', $pattern);

        return "/^$pattern$/";
    }
}
