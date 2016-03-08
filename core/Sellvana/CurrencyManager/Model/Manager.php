<?php

/**
 * Class Sellvana_CurrencyManager_Model_Manager
 *
 * @property FCom_Frontend_Main $FCom_Frontend_Main
 */
class Sellvana_CurrencyManager_Model_Manager extends BClass
{
    const SYMBOL_TYPE_NONE        = 1;
    const SYMBOL_TYPE_SYMBOL      = 2;
    const SYMBOL_TYPE_CODE        = 3;
    const SYMBOL_TYPE_NAME        = 4;

    const SYMBOL_POSITION_DEFAULT = 8;
    const SYMBOL_POSITION_RIGHT   = 16;
    const SYMBOL_POSITION_LEFT    = 32;

    /** @var string */
    protected static $_pattern;

    /**
     * @param bool|false $empty
     * @return array
     */
    public function getAvailableSymbolPositions($empty = false)
    {
        $positions = ($empty) ? ['' => ''] : [];
        $positions += [
            self::SYMBOL_POSITION_DEFAULT => _('Default'),
            self::SYMBOL_POSITION_RIGHT => _('Right'),
            self::SYMBOL_POSITION_LEFT => _('Left'),
        ];

        return $positions;
    }

    /**
     * @param bool|false $empty
     * @return array
     */
    public function getAvailableSymbolTypes($empty = false)
    {
        $types = ($empty) ? ['' => ''] : [];
        $types += [
            self::SYMBOL_TYPE_NONE        => _('Do not use'),
            self::SYMBOL_TYPE_SYMBOL      => _('Use symbol'),
            self::SYMBOL_TYPE_CODE        => _('Use code'),
            self::SYMBOL_TYPE_NAME        => _('Use name'),
        ];

        return $types;
    }

    /**
     * Prepare and set new formatter pattern
     *
     * @param NumberFormatter $formatter
     * @param float $value
     * @param string $currency
     * @return bool
     */
    public function setFormatterPattern(NumberFormatter &$formatter, $value, $currency)
    {
        $oldPattern = $formatter->getPattern();
        $precision = $this->getConfig('precision', $currency);
        $symbolType = $this->getConfig('symbol_type', $currency);
        $formatter = NumberFormatter::create($this->BLocale->getCurrentLocale(), NumberFormatter::DECIMAL);
        switch ($symbolType) {
            case self::SYMBOL_TYPE_NONE:
                $symbol = '';
                break;
            case self::SYMBOL_TYPE_SYMBOL:
                $symbol = $this->BLocale->getSymbol($currency);
                if (!$symbol) {
                    $symbol = $currency;
                }
                break;
            case self::SYMBOL_TYPE_NAME:
                $symbol = '¤¤¤';
                break;
            case self::SYMBOL_TYPE_CODE:
            default:
                $symbol = $currency;
                break;
        }

        $symbolPosition =  $this->getConfig('symbol_position', $currency);
        if ($symbolPosition == self::SYMBOL_POSITION_DEFAULT) {
            if (mb_substr($oldPattern, 0, 1) == '¤') {
                $symbolPosition = self::SYMBOL_POSITION_LEFT;
            } else {
                $symbolPosition = self::SYMBOL_POSITION_RIGHT;
            }
        }

        $space = $this->getConfig('symbol_space', $currency);
        // this is a non-breaking space, not normal one (ASCII code 160)
        if ($space && $symbolPosition == self::SYMBOL_POSITION_LEFT) {
            $symbol .= " ";
        } elseif ($space) {
            $symbol = " " . $symbol;
        }

        $newPattern = $this->_buildPattern($value, $currency, $precision, $symbol, $symbolPosition);
        $formatter->setPattern($newPattern);

        return true;
    }

    /**
     * Build a new pattern from passed parameters
     *
     * @param float $value
     * @param string $currency
     * @param int $precision
     * @param string $symbol
     * @param int $symbolPosition
     * @return string
     */
    protected function _buildPattern($value, $currency, $precision, $symbol, $symbolPosition)
    {
        $pattern = '#,##0';

        $zeroDecimal = (round($value, $precision) == round($value, 0));
        if ($zeroDecimal && $this->getConfig('cut_zero_decimals', $currency)) {
            $pattern .= "'" . $this->getConfig('replace_zero_decimals_with', $currency) . "'";
        } else {
            $pattern .= '.' . str_repeat('0', $precision);
        }


        if ($symbolPosition == self::SYMBOL_POSITION_LEFT) {
            $pattern = $symbol . $pattern;
        } else {
            $pattern .= $symbol;
        }

        return $pattern;
    }

    /**
     * Get config data for currency from global or currency specific options
     *
     * @param string $var
     * @param null|string $currency
     * @param null|mixed $default
     * @return mixed
     */
    public function getConfig($var, $currency = null, $default = null)
    {
        $globalValue = $this->BConfig->get('modules/Sellvana_CurrencyManager/' . $var, $default);
        $currencyValue = $this->BConfig->get('modules/Sellvana_CurrencyManager/' . $var . '_' . $currency, '');

        return ($currencyValue === '') ? $globalValue : $currencyValue;
    }

    /**
     * @return string[]
     */
    public function getAllViews()
    {
        $result = [];
        $layout = $this->FCom_Frontend_Main->getLayout();
        $views = $layout->collectAllViewsFiles('FCom_Frontend', true)->getAllViews();
        foreach ($views as $view) {
            $result[$view->getParam('view_name')] = $view->getParam('view_name');
        }

        return $result;
    }
}