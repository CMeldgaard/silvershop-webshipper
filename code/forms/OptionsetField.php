<?php

namespace Silvershop\Webshipper;

class OptionsetField extends \OptionsetField
{
    public function Field($properties = array())
    {
        $source = $this->getSource();
        $odd = 0;
        $options = array();

        if($source) {

            foreach($source as $value => $title) {

                // Ensure $title is safely cast
                if ( !($title instanceof \DBField) ) {
                    $title = \DBField::create_field('Text', $title);
                }

                $itemID = $this->ID() . '_' . preg_replace('/[^a-zA-Z0-9]/', '', $value);
                $odd = ($odd + 1) % 2;
                $extraClass = $odd ? 'odd' : 'even';
                $extraClass .= ' val' . preg_replace('/[^a-zA-Z0-9\-\_]/', '_', $value);

                if($title->value['droppoint']){
                    $extraClass .= ' droppoint';
                }

                if($title->value['selected']){
                    $extraClass .= ' selected';
                }

                $options[] = new \ArrayData(array(
                    'ID' => $itemID,
                    'Class' => $extraClass,
                    'Name' => $this->name,
                    'Value' => $value,
                    'Title' => $title->value['title'],
                    'Price' => \DBField::create_field('ShopCurrency',$title->value['price']),
                    'isChecked' => $title->value['selected'],
                    'isDisabled' => $this->disabled || in_array($value, $this->disabledItems),
                ));
            }
        }

        $properties = array_merge($properties, array(
            'Options' => new \ArrayList($options)
        ));

        return \FormField::Field($properties);
    }
}
