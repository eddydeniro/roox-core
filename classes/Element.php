<?php

namespace Roox;
use DOMDocument;

/**
 * Element
 * 
 * Edi Supriyadi
 * edisupr@gmail.com
 * Dec 2022
 * Revised on Dec 23 for Rukovoditel's compatibility
 * 
 * This class is for parsing and generating HTML elements.
 * 
 * How It Works
 * Basically it will form or convert array from/into HTML.
 * The array follows this pattern:
 * 1. Every element forms an associative array, except text node (directly as string value)
 * Example
 * HTML: <div class="myclass">SOME CONTENT <i>ITALIC</i></div>
 * Array: $thisElement = [
 *  'tag'=>'div',
 *  'class'=>'myclass',
 *  'content'=>[
 *      'SOME CONTENT',
 *      [
 *          'tag'=>'i',
 *          'content'=>['ITALIC']
 *      ]
 *  ]
 * ];
 * 
 * 2. Children of an element is contained in key "content" (see above example)
 * 3. Element children can have multiple texts and elements and forms a regular array, not associative.
 * 4. Every key in element's array will be translated into attributes
 * 5. There are some special keys in eleents array (pseudo attribute) for special purpose that is not translated into attribute (e.g. tag, content, contentPattern, selectedValue, marker)
 * 
 * Pseudo Attributes
 * a. selectedValue (select, checkbox, radio, switch)
 * b. content (all)
 * c. contentPattern (all)
 * d. condition (all)
 * e. marker (all)
 * f. @TODO prefix (input:text)
 * g. @TODO suffix (input:text)
 * 
 * Key contentPattern is a repeated contained elements, iterated from supplied data
 * 
 * condition is an array that change the html based on its evaluation.
 * The format: condition=>['%PHP Code to evaluate%', 'target', 
 * [array providing some variable to pick or evaluate. Reserved key is choices (to choose from when the code evaluates to true/false) ]]
 * For example, you want the class change from classA to classB if value of current element is 2,
 * you can write condition=>['%$value==2%','class',['classB', 'classA']]
 * Default target is content, but you can change it into contentPattern, class, as you like

 * 
 * 6. There are some special values that is marked with {...} (I called it 'placeholder').
 * It is a dynamic content that's replaceable with provided variable value, e.g. '{name}' will be replaced by the value of variable $name.
 * Some special placeholders:
 * a. {key} & {value} is to be replaced by iterated data, such as when creating option for element select.
 * b. {VALUE}, to differentiate with {value}, is replaced by a single variable $VALUE.
 * c. {name} is commonly for form elements, replaced with single variable $name.
 * d. {id} and all other placeholders is replaced with single variable with the same name.
 * e. {repeat} means that it should repeat from the most top pattern
 * Example 
 * $array = [
 *  textNode1,
 *  [
 *      tag=> div
 *      attr1=> value1,
 *      attr2=> value2,
 *      contentPattern=> [
 *          textNode1,
 *          [
 *              tag=> span
 *              content=> {repeat}
 *          ]
 *      ]
 *  ] 
 * ];
 * 
 * 
 * 
 * 
 */
class Element
{
    const   VAR_REPEAT = 'repeat',
            VAR_KEY = 'key',
            VAR_VALUE = 'value',
            VAR_PARENT_KEY = 'parentKey',
            VAR_PARENT_VALUE = 'parentValue',
            VAR_CONTENT_PATTERN = 'contentPattern',
            VAR_CONTENT = 'content',
            VAR_CONDITION = 'condition',
            VAR_MARKER = 'marker',
            VAR_TAG = 'tag',
            VAR_SELECTED = 'selectedValue',
            VAR_TYPE = 'elementType',
            VAR_DATAKEY = 'datakey';

    const   ICON = 'bi bi-square-fill';
    const   TEMPLATE_MARK = '_',
            REGULAR_MARK  = '-',
            FORM_CONTAINER_CLASS = 'form_container',
            BODY_DATA     = 'body';
    const ATTR_CHOICE = [
        'property'=>[
            'select'=>'selected',
            'checkbox'=>'checked',
            'radio'=>'checked',
            'switch'=>'checked'
        ],
        'targetElement'=>[
            'select'=>'option',
            'checkbox'=>'checkbox',
            'radio'=>'radio',
            'switch'=>'checkbox'    
        ]
    ];

    const ALT_VARS = [
        '<>'=>self::VAR_TAG,
        '.'=>'class',
        '#'=>'id',
        '*'=>'name',
        '='=>'value',
        '>'=>self::VAR_CONTENT
    ];

    const INPUT_TYPES = [
        'text', 'password', 'email', 'search', 'tel', 'url', 'number', 'range', 'datetime-local', 
        'month', 'time', 'week', 'date', 'color', 'hidden', 'file'
    ];

    const SELF_CLOSING_TAG = ['br', 'col', 'hr', 'img', 'input', 'link', 'meta', 'area', 'base','command', 'embed', 'keygen', 'param', 'source', 'track', 'wbr'];

    const BUTTON_TYPES = ['submit', 'reset', 'button'];

    //element with alias which actually consist of or is built with another HTML element. 
    const PSEUDO_ELEMENTS = ['tab', 'accordion', 'nestedList', 'container', 'formContainer', 'modal'];
    /**
     * default_options
     *
     * This is default arguments that is required for some condition.
     * - alwaysOpen is for accordion and nestedList so when we expand one section, the other sections will stay open,
     *   not like common accordion that lets only one section to open.
     * - hideIfNoData will delete placeholders ({...}) when there's no variable it matches.
     * - activeSection is used for tab, accordion, and nestedList to define which section should be expanded
     * - [DISCARDED] isContainer means if there is marked element (having key marker as the value), place in recent created array if there is placeholder with the same name.
     *   Example:
     *   Supplied array: [
     *     tag=>div,
     *     content=>[
     *        [marker1],
     *        [marker2]
     *     ]
     *   ]
     * 
     * @var array
     */
    protected $default_options = [
        'alwaysOpen' => false,
        'hideIfNoData' => true,
        'activeSection'=> 0,
        //'isContainer'=>false,
        'groupData'=>false,
        'plainText' =>false,
        'withFormTag'=>true,
        'withSubmit'=>true
    ];
        
    /**
     * default_template
     *
     * This is built-in data html array that can be used directly to create an element.
     * Most element patterns are compatible with Bootstrap 3.3.7 (Initially this is for Bootstrap 5, but I have to dowgraded for Rukovoditel).
     * This data can be replaced with class constructor or via method setDefault.
     *  
     * @var array
     */
    protected $default_template = [
        'label'=> [
            self::VAR_TAG=>'label',
            'class'=> 'col-sm-2 control-label',
            self::VAR_CONTENT=>['{VALUE}']
        ],
        'input'=> [
            self::VAR_TAG=>'input',
            'type'=>'text',
            'name'=>'{name}',
            'id'=>'{id}',
            'value'=>'{VALUE}',
            'class'=>'form-control'
        ],
        'hidden'=> [
            self::VAR_TAG=>'input',
            'type'=>'hidden',
            'name'=>'{name}',
            'value'=>'{VALUE}',
        ],        
        'color'=> [
            self::VAR_TAG=>'input',
            'type'=>'color',
            'name'=>'{name}',
            'id'=>'{id}',
            'value'=>'{VALUE}',
            'class'=>'form-control'
        ],        
        'textarea'=> [
            self::VAR_TAG=>'textarea',
            'name'=>'{name}',
            'id'=>'{id}',
            'class'=>'form-control',
            'content'=>['{VALUE}']
        ],
        'multiselect'=> [
            'tag'=>'select',
            'name'=>'{name}',
            'id'=>'{id}',
            'class'=>'form-control chosen-select {extra_class}',
            'multiple'=>'multiple',
            'selectedValue' => '{VALUE}',
            'contentPattern'=>[
                [
                    'tag'=>'option',
                    'value'=>'{key}',
                    'content'=>['{value}']    
                ]
            ]
        ],        
        'select'=> [ //the select still cannot interpret the grouping options. IT SHOULD BE FIXED!
            self::VAR_TAG=>'select',
            'name'=>'{name}',
            'id'=>'{id}',            
            'class'=>'form-control {extra_class}',
            self::VAR_SELECTED => '{VALUE}', //This key is for treated to be selected in later function and will be unset when creating html
            self::VAR_CONTENT_PATTERN=>[
                [
                    self::VAR_TAG=>'option',
                    'value'=>'{key}',
                    self::VAR_CONTENT=>['{value}']    
                ]
            ]
        ],
        'checkbox'=> [
            self::VAR_SELECTED => '{VALUE}', 
            self::VAR_CONTENT_PATTERN=>[
                [
                    self::VAR_TAG=>'div',
                    'class'=>'checkbox',
                    self::VAR_CONTENT=>[
                        [
                            self::VAR_TAG=>'label',
                            self::VAR_CONTENT=>[
                                [
                                    self::VAR_TAG=>'input',
                                    'type'=>'checkbox',
                                    'name'=>'{name}',
                                    'value'=>'{key}',
                                    'id'=>'{id}_{key}'
                                ],        
                                '{value}'
                            ]
                        ]
                    ]
                ]
            ]
        ],
        'radio'=> [
            self::VAR_SELECTED => '{VALUE}', 
            self::VAR_CONTENT_PATTERN=>[
                [
                    self::VAR_TAG=>'label',
                    'class'=>'radio-inline',
                    self::VAR_CONTENT=>[
                        [
                            self::VAR_TAG=>'input',
                            'type'=>'radio',
                            'name'=>'{name}',
                            'value'=>'{key}',
                            'id'=>'{id}_{key}'
                        ],        
                        '{value}'
                    ]
                ]
            ]
        ],
        'button'=>[
            self::VAR_TAG=>'input',
            'type'=>'button',
            'value'=>'{VALUE}',
            'class'=>'btn btn-primary'
        ],
        'table'=>[
            self::VAR_TAG=>'table',
            'class'=>'table table-hover table-striped {extra_class}',
            'id'=>'{id}',
            self::VAR_CONTENT=>[
                [
                    self::VAR_TAG=>'thead',
                    self::VAR_DATAKEY=>'header',
                    self::VAR_CONTENT=>[
                        [
                            self::VAR_TAG=>'tr',
                            self::VAR_CONTENT_PATTERN=>[
                                [
                                    self::VAR_TAG=>'th',
                                    'scope'=>'col',
                                    self::VAR_CONTENT=>['{value}']
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    self::VAR_TAG=>'tbody',
                    self::VAR_DATAKEY=>self::BODY_DATA,
                    self::VAR_CONTENT_PATTERN=>[
                        [
                            self::VAR_TAG=>'tr',
                            self::VAR_CONTENT_PATTERN=>[
                                [
                                    self::VAR_TAG=>'td',
                                    self::VAR_CONTENT=>['{value}']    
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],
        'container'=>[
            self::VAR_TAG=>'div',
            'class'=>'form-group',
            self::VAR_CONTENT=>
            [
                [
                    self::VAR_TAG=>'label',
                    'class'=> 'col-md-3 control-label',
                    self::VAR_CONTENT=>['{label}']
                ],
                [
                    self::VAR_TAG=>'div',
                    'class'=>'col-md-9',
                    self::VAR_CONTENT=>[
                        '{{element}}'
                    ]
                ]            
            
            ]                
        ],
        'accordion'=>[
            self::VAR_TAG=>'div',
            'class'=>'panel-group',
            'role'=>'tablist',
            'aria-multiselectable'=>"true",
            'id'=>'accordion_{id}',
            self::VAR_CONTENT_PATTERN=>[
                [
                    self::VAR_TAG=>'div',
                    'class'=>'panel panel-default',
                    self::VAR_CONTENT=>[
                        [
                            self::VAR_TAG=>'div',
                            'class'=>'panel-heading',
                            'role'=>'tab',
                            'id'=>'heading_{key}',
                            self::VAR_CONTENT=>[
                                [
                                    self::VAR_TAG=>'h4',
                                    'class'=>'panel-title',
                                    self::VAR_CONTENT=>[
                                        [
                                            self::VAR_TAG=>'a',
                                            'data-toggle'=>'collapse',
                                            'data-parent'=>'#accordion_{id}',
                                            'href'=>'#{key}',
                                            'aria-expanded'=>'{expanded}',
                                            'aria-controls'=>'{key}',
                                            self::VAR_CONTENT=>['<b>{value}</b>']
                                        ]
                                    ]
                                ]
                            ]    
                        ],
                        [
                            self::VAR_TAG=>'div',
                            'id'=>'{key}',
                            'class'=>'panel-collapse collapse {show}',
                            'role'=>'tabpanel',
                            'aria-labelledby'=>'heading_{key}',
                            self::VAR_CONTENT_PATTERN=>[
                                [
                                    self::VAR_TAG=>'div',
                                    'class'=>'panel-body',
                                    self::VAR_CONTENT=>['{value}']
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],
        'tab'=>[
            self::VAR_TAG=>'div',
            'class'=>'tabbable tabbable-custom',
            self::VAR_CONTENT=>
            [
                [
                    self::VAR_TAG=>'ul',
                    'class'=>'nav nav-tabs',
                    'id'=>'{id}',
                    'role'=>'tablist',
                    self::VAR_DATAKEY=>'header',
                    self::VAR_CONTENT_PATTERN=>[
                        [
                            self::VAR_TAG=>'li',
                            'class'=>'{active}',
                            'role'=>'presentation',
                            self::VAR_CONTENT=>[
                                [
                                    self::VAR_TAG=>'a',
                                    'class'=>'tab_switcher',
                                    'data-toggle'=>'tab',
                                    'role'=>'tab',
                                    'href'=>'#tab-body-{key}',
                                    'aria-controls'=>'tab-body-{key}',
                                    self::VAR_CONTENT=>['{value}']
                                ]
                            ]
                        ]
                    ]    
                ],
                [
                    self::VAR_TAG=>'div',
                    'class'=>'tab-content',
                    self::VAR_DATAKEY=>self::BODY_DATA,
                    self::VAR_CONTENT_PATTERN=>[
                        [
                            self::VAR_TAG=>'div',
                            'class'=>'tab-pane bg-white fade',
                            'style'=>'padding:20px;',
                            'id'=>'tab-body-{key}',
                            'role'=>'tabpanel',
                            'aria-labelledby'=>'tab-{key}',
                            'tabindex'=>'0',
                            self::VAR_CONTENT=>['{value}']
                        ]
                    ]
                ]                    
            ]

        ],
        'form'=>[
            self::VAR_TAG=>'form',
            'method'=>'post',
            'action'=>'{action}',
            'class'=>'form-horizontal',
            self::VAR_CONTENT=>[]
        ],
        'formContainer'=>[
            self::VAR_TAG=>'div',
            'class'=>self::FORM_CONTAINER_CLASS,
            self::VAR_CONTENT=>[]
        ],        
        'nestedList'=>[            
            self::VAR_TAG=>'ul',
            'class'=>'list-unstyled ps-0 nested_list {isAccordion}',
            self::VAR_CONTENT_PATTERN => [
                [
                    self::VAR_TAG=>'li',
                    'class'=>'pb-1',
                    self::VAR_CONDITION=>['%count($contentData)%', self::VAR_CONTENT, [
                        [
                            [
                                self::VAR_TAG=>'button',
                                'class'=>'btn btn-toggle align-items-center {collapsed}',
                                'style'=>'padding-left:{leftPadding}px;',
                                'data-bs-toggle'=>"collapse",
                                'data-bs-target'=>'#{key}-collapse', 
                                'aria-expanded'=> '{expanded}',
                                self::VAR_CONTENT=>[
                                    '{value}'
                                ]
                            ],
                            [
                                self::VAR_TAG=>'i',
                                'class'=>"bi-chevron-left"
                            ],
                            [
                                'class'=>'collapse {show}',
                                'id'=>'{key}-collapse',
                                self::VAR_CONTENT=>[
                                    [
                                        self::VAR_TAG=>'ul',
                                        'class'=>"btn-toggle-nav list-unstyled fw-normal pb-1",
                                        self::VAR_CONTENT=>[
                                            [
                                                self::VAR_TAG=>'li',
                                                self::VAR_CONTENT=>'{repeat}'
                                            ]
                                        ]      
                                    ]
                                ]
                            ]    
                        ],
                        [
                            [
                                self::VAR_TAG=>'a',
                                'href'=> '%base_url($key)%',
                                'class'=>'{key}',
                                'style'=>'padding-left:{leftPadding}px;',
                                self::VAR_CONTENT=>[
                                    '{icon} {value}'
                                ]    
                            ]
                        ]

                    ]],
                    self::VAR_CONTENT=>[]
                ]
            ]
        ],
        'script'=>[
            self::VAR_TAG=>'script',
            self::VAR_CONTENT=>['{VALUE}']
        ],
        'style'=>[
            self::VAR_TAG=>'style',
            self::VAR_CONTENT=>['{VALUE}']
        ],
        'modal'=>[
            'tag'=>'div',
            'class'=>'modal fade',
            'id'=>'{id}',
            'aria-hidden'=>'true',
            'tabindex'=>'-1',
            self::VAR_CONTENT=>[
                'class'=>'modal-content',
                self::VAR_CONTENT=>[
                    [
                        'class'=>'modal-header',
                        self::VAR_CONTENT=>[
                            [
                                self::VAR_TAG=>'h1',
                                'class'=>'modal-title fs-5',
                                self::VAR_CONTENT=>['{title}']
                            ],
                            [
                                self::VAR_TAG=>'button',
                                'type'=>'button',
                                'class'=>'btn-close',
                                'data-bs-dismiss'=>'modal',
                                'aria-label'=>'Close'
                            ]
                        ]
                    ],
                    [
                        'class'=>'modal-body',
                        self::VAR_CONTENT=>['{body}']
                    ],
                    [
                        'class'=>'modal-footer',
                        self::VAR_CONTENT=>['{footer}']
                    ]
                ]
            ]
        ],

    ];
    
    /**
     * html_mode
     * 
     * If html_mode == true, all processing directly parsed into html string,  which is faster.
     * Otherwise, it uses array, bit slower but useful for certain situation.
     * 
     * @var bool
     */
    protected $html_mode = true;
        
    /**
     * html_array
     *  
     * This array will contain all array when html_mode == false
     * 
     * @var array
     */
    protected $html_array = [];
        
    /**
     * html_string
     *
     * This array will contain all html string when html_mode == true
     * 
     * @var array
     */
    protected $html_string = [];
    
    protected $markeredElements = [];

    protected $directMarkeredReplacement = true;

    /**
     * __construct
     *
     * You can load your own templates that will be merged into built-in templates.
     * The template can have the same name that will replace the built-in one.
     * For custom template, you can call it with method fromType(...args)
     * 
     * @param  array $loadDefaults
     * @return void
     */
    function __construct(array $templates=[])
    {
        $this->setTemplates($templates);
    }

    /**
     * isAssociative
     *
     * Check if an array is associative or regular
     * 
     * @param  mixed        $a
     * @return boolean
     */
    private static function isAssociative($a) {
        if(!is_array($a))
        {
            return false;
        }
        return is_string(key($a)) || (is_int(key($a)) && key($a));
    }
    
    /**
     * translateCode
     *
     * @param  string $string
     * @param  array $variables
     * @return string
     */
    private static function translateCode(string $string, array $variables = [])
    {
        //I have to create random variables out of existing ones, in case there's the same variable in $variables
        preg_match_all('/%+(.*?)%/', $string, $ttrmtrpret); 
        $resxxx = $ttrmtrpret[1];
        if(!count($resxxx))
        {
            return $string;
        }

        $string8934jkejwrke = $string;
        unset($string);

        if(count($variables))
        {
            $variables9ior34mek = $variables;
            unset($variables);
            foreach ($variables9ior34mek as $xcvdfdf => $ryryty) 
            {
                $$xcvdfdf = $ryryty;
            }    
        }
        foreach ($resxxx as $cxxx) 
        {
            try {
                eval('$jkjkjkjk = '.$cxxx.';');
                $string8934jkejwrke = str_replace("%$cxxx%", $jkjkjkjk, $string8934jkejwrke);
            }
            catch (\Exception $e) {
                //code to handle the exception
            }
        }
        return $string8934jkejwrke;
    }
    
    /**
     * placeInContainer
     *
     * @param  mixed $container
     * @return mixed
     */
    private function placeInContainer($container)
    {
        $string = $container;
        if(is_array($container))
        {
            $string = json_encode($container);
        }
        if(!$string)
        {
            return $container;
        }
        //Search all text inside double curly brackets
        preg_match_all('/{{(.*?)}}/', $string, $output);
        $placeholders = $output[1];

        if(count($placeholders))
        {
            foreach ($placeholders as $marker) {
                $marker = trim($marker);
                if(!$marker)
                {
                    continue;
                }
                if(isset($this->markeredElements[$marker]))
                {
                    $string = str_replace("{{{$marker}}}", $this->markeredElements[$marker], $string);
                }
            }
        }

        if(is_array($container))
        {
            return json_decode($string, true);
        }        

        return $string;
    }    

    /**
     * addData
     *
     * @param  mixed $htmlArray
     * @return object
     */
    private function addData($data)
    {
        if($this->html_mode)
        {
            $this->html_string[] = $data;
        }
        else
        {
            if(self::isAssociative($data))
            {
                $data = [$data];
            }
            $this->html_array[] = $data;
        }

        return $this;
    }    
    
    /**
     * interpret
     *
     * @param  string $string
     * @param  array $args
     * @return string
     */
    private static function interpret(string $string, array $options = [], $attrKey = '')
    {
        if(!is_string($string))
        {
            return $string;
        }
        if(!count($options) && !$options['hideIfNoData'])
        {
            return $string;
        }
        //No PHP code for style because it may contain %...%
        if($attrKey!='style')
        {
            $string = self::translateCode($string, $options);
        }
        //search string between single bracket (excluding double brackets)
        //exactly double brackets {+\b(.*?)\b}+} ?
        preg_match_all('/{{1}(.*?)}{1}/', $string, $output);
        if(!count($output[0]))
        {
            return $string;
        }
        $placeholders = array_filter($output[0], function($item){
            return substr($item,0,2)!='{{';
        });
        foreach($placeholders as $var)
        {
            $v = trim(str_replace(['{','}'],['',''], $var));
            if(isset($options[$v]))
            {
                $string = str_replace($var, $options[$v], $string);
            }
            else
            {
                $string = $options['hideIfNoData'] ? str_replace($var, '', $string) : $string;
                //$string = $options['hideIfNoData'] && !(count($markedElements) && in_array($var, $markedElements))? str_replace($var, '', $string) : $string;
            }                
        }
        return $string;
    }
    
    /**
     * dataActivator
     *
     * This method used for tab, accordion, and nestedList to give the proper attribute for the expanded or collapsed section
     * 
     * @param  string $type
     * @param  array $data
     * @param  mixed $activeSection
     * @param  bool $alwaysOpen
     * 
     * @return array
     */
    private static function dataActivator(string $type, array $data, $activeSection, bool $alwaysOpen = false)
    {
        $n = 1;
        if(!$alwaysOpen && $activeSection && is_string($activeSection))
        {
            $tmp = explode(',', $activeSection);
            $activeSection = $tmp[0];
        }

        foreach($data as $key=>$value)
        {
            if(is_string($value))
            {
                $data[$key] = [$value, [], []];
            }    

            $comparator = $key;
            $isNested = $type=='nestedList' && isset($value[2]) && count($value[2]);
            $check = is_numeric($activeSection) ? $activeSection == $comparator : in_array($comparator, explode(",", $activeSection));

            $expanded = $check ? 'true' : 'false';
            $collapsed = $check ? '' : 'collapsed';
            $show = $check ? 'in' : ''; //In BS 3.3.7, it is marked with "in", not "show"
            $active = $check ? 'active' : '';

            if($type=='tab')
            {
                $data[$key][1] = array_merge($data[$key][1], ['active'=>$active, 'selected'=>$expanded, 'show'=>$show]);
            }            

            if($type=='accordion' || ($type=='nestedList' && $isNested))
            {
                $var = $value[1] ?? [];
                $data[$key][1] = array_merge($var, ['expanded'=>$expanded, 'collapsed'=>$collapsed, 'show'=>$show]);
                if($isNested)
                {
                    $data[$key][2] = self::dataActivator($type, $value[2], $activeSection, $alwaysOpen);
                }
            }
            $n++;
        }
        return $data;
    }

    private static function elementNameToId($name)
    {
        $search = ["[", "]", " ", "{", "}", "#"];
        $replace = array_fill(0, count($search), "");
        return str_replace($search, $replace, $name);
    }

    /**
     * elementToArray
     *
     * @param  object $element
     * @return array
     */
    private static function elementToArray(object $element)
    {
        //Let's exclude html and body because it always included even if we start from another tag
        if ($element->tagName != 'html' && $element->tagName != 'body') {
            $obj = array(self::VAR_TAG => $element->tagName);
            foreach ($element->attributes as $attribute) {
                $obj[$attribute->name] = $attribute->value;
            }
        }
        foreach ($element->childNodes as $subElement) {
            if ($subElement->nodeType == XML_TEXT_NODE) {
                $text = trim($subElement->textContent);
                if ($text) {
                    $obj[self::VAR_CONTENT][] = $text;
                }
            } else {
                if ($element->tagName == 'html' || $element->tagName == 'body') {
                    $obj = self::elementToArray($subElement);
                    continue;
                }
                $obj[self::VAR_CONTENT][] = self::elementToArray($subElement);
            }
        }
        return $obj;
    }

    private function addMarkeredElements($element, $marker)
    {
        if($marker)
        {
            //Check first if any marker is available for existing placeholder
            $element = $this->placeInContainer($element);
            $this->markeredElements[$marker] = $element;
        }
    }

    /**
     * parse
     * data format for first-tier element: 
     * [ key=>[value, [variables], [contentPattern data] ]]
     * Example:
     * [ 'route_1'=>
     *  [
     *      'Route 1', 
     *      ['varKey1'=>'varValue1', 'varKey2'=>'varValue2'], 
     *      [
     *          etc
     *      ] 
     *  ]
     * ]
     * 
     * @param  mixed $elements
     * @param  mixed $data
     * @param  mixed $options
     * @return mixed
     */
    private function parse($elements, $data = NULL, array $options = [], bool $htmlOutput = true)
    {
        $choices_attributes = self::ATTR_CHOICE;
        $self_closing_tag = self::SELF_CLOSING_TAG;
        $preservedAttributes = [
            self::VAR_TAG,self::VAR_CONTENT,self::VAR_CONTENT_PATTERN,
            self::VAR_SELECTED, self::VAR_CONDITION, 
            self::VAR_TYPE, 
            self::VAR_DATAKEY
        ];

        if(!$this->html_mode)
        {
            $preservedAttributes[] = self::VAR_MARKER;
        }

        $result = [];
        $htmlResult = '';
        $singleElement = false;
        $cTag = self::VAR_TAG;
        $cKey = self::VAR_KEY;
        $cValue = self::VAR_VALUE;
        $cRepeat = self::VAR_REPEAT;
        $cContentPattern = self::VAR_CONTENT_PATTERN;
        $cContent = self::VAR_CONTENT;
        $cCondition = self::VAR_CONDITION;
        $cSelected = self::VAR_SELECTED;
        $cDatakey = self::VAR_DATAKEY;

        if(self::isAssociative($elements))
        {
            $elements = [$elements];
            $singleElement = true;
        }

        
        $elementsData = $options['contentData'] ?? ($data[self::BODY_DATA] ?? $data);
        $parentData = $options['parentData'];

        //Translate variables that is specific to this cycle
        $variables = $options;
        if(isset($options['variables']) && count($options['variables']))
        {
            foreach($options['variables'] as $oKey=>$oVal)
            {
                if(is_string($oVal))
                {
                    $oVal = self::interpret($oVal, $variables);
                }   
                $variables[$oKey] = $oVal;
                $options[$oKey] = $oVal;
            }
            unset($options['variables']);
        } 

        $choicesTags = array_keys($choices_attributes['property']);
        //Iterate over group of elements
        foreach ($elements as $n => $element) 
        {
            if(is_string($element))
            {
                $result[$n] = self::interpret($element, $variables);
                $htmlResult .= $result[$n];
                continue;
            }
            $element_keys = array_keys($element);
            $element_values = array_values($element);
            $element_keys = json_decode(str_replace(array_keys(self::ALT_VARS), array_values(self::ALT_VARS), json_encode($element_keys)), true);
            $element = array_combine($element_keys, $element_values);
            $result[$n] = $element;
            $marker = $element['marker'] ?? '';
            $tag = $element[$cTag] ?? 'div';
            $inputType = $tag=='input' ? (isset($element['type']) ? $element['type'] : 'text') :'';
            $isChoiceInput = in_array($inputType, $choicesTags);
            $isFormInputs = in_array($tag, ['select','textarea']) || ($tag=='input' && in_array($inputType, self::INPUT_TYPES)) || $isChoiceInput;
            $currentKey = $options[$cKey] ?? 0;
            $elementType = $inputType ? $inputType : $tag;
            $plainText = isset($options['plainText']) ? $options['plainText'] : false;
            //Lets adjust the colspan if only there is one data in a row while the header is > 1
            if($tag == 'tr')
            {
                if(count($elementsData)==1 && count($parentData['header']))
                {
                    $element[$cContentPattern][0]['colspan'] = count($parentData['header']);
                }                    
            }
            if($isFormInputs)
            {      
                $checkInputTypes = $inputType && $inputType!='hidden';
                if($checkInputTypes && $plainText)
                {
                    $text = trim(isset($element['value']) ? $element['value'] : "");
                    switch ($inputType) {
                        case 'color':
                            $icon = self::ICON;
                            $text = "<i style='color:$text;' class='$icon'></i> ".$text;
                            break;

                        case 'url':
                            $text = "<a href='$text'>$text</a>";
                            break;                        

                        case 'email':
                            $text = "<a href='mailto:$text'>$text</a>";
                            break;

                        case 'password':
                            $text = "******";
                            break;

                        default:
                            break;
                    }
                    if($marker)
                    {
                        $this->addMarkeredElements($text, $marker);
                        if(!$htmlOutput)
                        {
                            unset($result[$n]);
                        }
                        continue;
                    }
                    $result[$n] = $text; 
                    $htmlResult .= $text;
                    continue; //STOP AND NEXT
                }

                $name = $isFormInputs ? ($element['name'] ? $element['name'] : $elementType."-".$currentKey.rand(100,900)) : '';
                $groupData = $options['groupData'] ?? [];
        
                if(($tag=='select' && isset($element['multiple'])) || ($isChoiceInput && $inputType!='radio' && $groupData>1))
                {
                    $name = $name ? $name : $tag."-".$currentKey.rand(100,900);
                    $name = (strpos($name, '[]') !== FALSE) ? $name : $name.'[]';
                    $result[$n]['name'] = $name;
                    $element['name'] = $name;
                }
            }

            //POST MARKING (see below for pre)
            if((isset($options['targetElement']) || isset($options[$cSelected])) && isset($element[$cTag]))
            {
                $target = $options['targetElement'] ?? '';
                $parentTag = $options[$cTag.'_choices'] ?? '';
                if(!$target)
                {
                    $parentTag = $isChoiceInput ? $inputType : $parentTag;
                }                
                $selectedValue =  $options[$cSelected] ?? 0;
                $currentTag = $element[$cTag];
                $property = $choices_attributes['property'][$parentTag] ?? '';

                if($target==$currentTag || $property)
                {
                    $check = is_numeric($selectedValue) ? $selectedValue == $currentKey : in_array($currentKey, explode(",", $selectedValue));
                    if($check && $property)
                    {
                        $result[$n][$property] = '';
                        $element[$property] = ''; //So html generator will capture it
                    }                          
                }
            }
            //ENDOFPOSTMARKING

            $datakey = $element[$cDatakey] ?? '';
            $dataRef = $datakey && isset($parentData[$datakey]) ? $parentData[$datakey] : $elementsData;
            if(isset($element[$cDatakey]))
            {
                unset($result[$n][$cDatakey]);
            }

            //PRE MARKING (see above for the post)
            if(isset($element[$cSelected]))
            {
                $selectedValue = self::interpret($element[$cSelected], $variables);
                unset($result[$n][$cSelected]);
                $options['choiceElement'] = true;
                if($plainText && $element[$cSelected])
                {
                    unset($result[$n]);
                    $search = array_keys($dataRef); $search[] = ',';
                    $replacer = array_map(function($item){
                        return is_array($item) ? $item[0] : $item;
                    }, array_values($dataRef)); $replacer[] = ', ';
                    $text = trim(str_replace($search, $replacer, $selectedValue)," ,");

                    if($marker)
                    {
                        $this->addMarkeredElements($text, $marker);
                        if(!$htmlOutput)
                        {
                            unset($result[$n]);
                        }
                        continue;
                    }

                    $result[$n] = $text;
                    $htmlResult .= $text;
                    continue; //STOP AND NEXT
                }
                if($selectedValue)
                {
                    if($tag=='select' && !isset($element['multiple']))
                    {
                        if(!is_numeric($selectedValue))
                        {
                            $result[$n]['name'] = (strpos($result[$n]['name'], '[]') !== FALSE) ? $result[$n]['name'] : $result[$n]['name'].'[]';
                            $result[$n]['multiple'] = '';
                            $element['multiple'] = ''; //So html generator will capture it
                            $element['name'] = $result[$n]['name'];
                        }
                    }
                    $options['targetElement'] =  in_array($tag, $choicesTags) ? $choices_attributes['targetElement'][$tag] : '';
                    $options[$cTag.'_choices'] = $tag;
                    $options[$cSelected] = $selectedValue;    
                }
            }
            //ENDOFPREMARKING

            if(isset($element[$cCondition]))
            {
                $conditional = $element[$cCondition][0];
                $conditional_target = $element[$cCondition][1];                
                $conditional_choices = $element[$cCondition][2];
                $check = self::translateCode($conditional, $variables);
                $element[$conditional_target] = $check ? $conditional_choices[0] : $conditional_choices[1];
                unset($result[$n][$cCondition]);
                unset($element[$cCondition]);
            }
            
            $currentOptions = $options;
            $attributeText = [];
            $attribute = '';
            $inner = '';

            //Iterate over a single elements
            foreach ($element as $attrKey => $attrValue) 
            {
                //If value = {self::VAR_REPEAT}, change it to initial pattern
                if($attrValue=="{{$cRepeat}}")
                {
                    $attrValue = $options['parentPattern'];
                }
                if($attrKey==$cContentPattern)
                {
                    unset($result[$n][$attrKey]);
                    if(count($dataRef))
                    {
                        $elementGroup = $attrValue;
                        $currentOptions['groupData'] = count($dataRef);
                        //Iterate over a data
                        foreach ($dataRef as $key => $value) 
                        {
                            //Remember the $value here can be an array ([$actualValue, $variableArray, $subContentArray]) or a string
                            if(is_string($value))
                            {
                                $value = [$value, [], []];
                            }
                            $currentOptions[$cKey] = $key;
                            //$currentOptions[$cValue] = $value[0];
                            $currentOptions[$cValue] = is_array($value) ? $value[0] : $value;
                            $currentOptions['variables'] = isset($dataRef[$key][1]) && is_array($dataRef[$key][1]) ? $dataRef[$key][1] : [];
                            $currentOptions['contentData'] = isset($dataRef[$key][2]) && is_array($dataRef[$key][2]) ? $dataRef[$key][2] : [];
                            
                            //Iterate over each element in contentPattern
                            foreach ($elementGroup as $eachElement) 
                            {
                                if(is_string($eachElement))
                                {
                                    $content = self::interpret($eachElement, $variables);
                                    $result[$n][$cContent][] = $content;
                                    $inner .= $content;
                                    continue;
                                }
                                $content = $this->parse($eachElement, $value, $currentOptions, $htmlOutput);       
                                $result[$n][$cContent][] = $content;
                                if($this->html_mode)
                                {
                                    $inner .= $content;
                                }
                            }
                        }
                    }
                }
                elseif($attrKey==$cContent)
                {
                    //Still brings the data for any contentPattern found later
                    $content = $this->parse($attrValue, $dataRef, $options, $htmlOutput);
                    $result[$n][$attrKey] = $content;
                    if($htmlOutput)
                    {
                        $inner .= $content;
                    }
                }
                else
                {
                    //style will not be interpreted so I have to insert the attrKey
                    $attrValue = trim(self::interpret($attrValue, $options, $attrKey));

                    $result[$n][$attrKey] = $attrValue;
                    if(!in_array($attrKey, $preservedAttributes))
                    {
                        $attributeText[] = "$attrKey='$attrValue'";
                    }
                }
            }
            if(count($attributeText))
            {
                $attribute = " ".implode(" ", $attributeText);
            }

            $htmlResultTemp = "<{$tag}{$attribute}>"; 
            if(!in_array($tag, $self_closing_tag) && $htmlOutput)
            {
                $htmlResultTemp .= "$inner</$tag>";
            }
            if($marker)
            {        
                $currentElement = $htmlOutput ? $htmlResultTemp : $result[$n];
                $this->addMarkeredElements($currentElement, $marker);
                if(!$htmlOutput)
                {
                    unset($result[$n]);
                }
                continue;
            }
            $htmlResult .= $htmlResultTemp;
        }
        if($htmlOutput)
        {
            return $htmlResult;
        }
        return $singleElement ? $result[0] : $result;
    }
    
    private function typeToTag($type, $elements)
    {
        if(in_array($type, self::INPUT_TYPES) || in_array($type, self::BUTTON_TYPES) || in_array($type, ['checkbox', 'radio', 'switch'])) 
        {
            return 'input';
        }

        if(in_array($type, ['form', 'formContainer'])) 
        {
            return 'form';
        }
        $elements = $this->getTemplate($type);
        if(!count($elements))
        {
            return 'div';
        }
        if(!self::isAssociative($elements))
        {
            $elements = $elements[0];
        }
        $return = $elements['tag'] ?? 'div';
        if(isset($elements['class']))
        {
            $return .= ".".str_replace(' ', ',',$elements['class']);
        }
        return $return;
    }

    /**
     * byType
     *
     * @param  string $type
     * @param  array $data
     * @param  array $options
     * @return array
     */
    function byType(string $type, array $data = [], array $options = [])
    {
        if(!$type)
        {
            return [];
        }

        $patternType = $type;
        $options[self::VAR_TYPE] = $type;
        $cDatakey = self::VAR_DATAKEY;

        if($type=='formTemplate')
        {
            $withFormTag = $options['withFormTag'] ?? true;
            $withSubmit = $options['withSubmit'] ?? true;
            $elements = $withFormTag ? $this->getTemplate('form') : $this->getTemplate('formContainer');
            if(!$withFormTag)
            {
                //To make sure the form container having class self::FROM_CONTAINER_CLASS so that applyAttribute will target correctly
                $classes = $elements['class'] ?? '';
                if(!in_array(self::FORM_CONTAINER_CLASS, explode(' ', $classes)))
                {
                    $elements['class'] .= ' '.self::FORM_CONTAINER_CLASS;
                }
            }
            $alter_data = [];
            foreach($data as $value)
            {
                $data_keys = array_keys($value);
                $data_values = array_values($value);
                $data_keys = json_decode(str_replace(array_keys(self::ALT_VARS), array_values(self::ALT_VARS), json_encode($data_keys)), true);
                $alter_data[] = array_combine($data_keys, $data_values);    
            }

            $data = $alter_data;

            $names = [];
            $ids = [];
            // $choices_tags = array_keys(self::ATTR_CHOICE['property']);

            $default_options = [
                'name'=>'name',
                'id'=>'id',
                'VALUE'=>'VALUE'
            ];
            $isSubmitExist = !$withSubmit;
            $current_data = [];

            foreach ($data as $key => $value) 
            {

                if(!isset($value['element']))
                {
                    continue;
                }
                $current_type = $value['element'];
                
                if(!in_array($current_type, array_keys($this->default_template)) && !in_array($current_type, self::INPUT_TYPES))
                {
                    $element = $value;
                    $element[self::VAR_TAG] = $value['element'];
                    unset($value['element']);
                    $elements[self::VAR_CONTENT][] = $element;
                    continue;                    
                }
                //if $current_type is in default_template but not input or
                // if(in_array($current_type, array_keys($this->default_template)))
                // {
                //     $element = $value;
                //     $element[self::VAR_TAG] = $value['element'];
                //     unset($value['element']);
                //     $elements[self::VAR_CONTENT][] = $element;
                //     continue;                    
                // }                 
                if($current_type=='submit')
                {
                    $isSubmitExist = true;
                }
                $default_options['name'] = $value['name'] ?? $value['element']."-".$key.rand(100,900);

                if(in_array($current_type, ['checkbox']))
                {
                    if(count($value['choices'])>1)
                    {
                        $default_options['name'] = (strpos($default_options['name'], '[]') !== FALSE) ? $default_options['name'] : $default_options['name'].'[]'; 
                    }
                }    

                if(!in_array($value['name'], $names))
                {
                    $names[] = $value['name'];
                }
                //unset($value['name']);

                $label_value = $value['label'] ?? '';
                //unset($value['label']);

                if($current_type!='hidden')
                {
                    $default_options['id'] = $value['id'] ?? self::elementNameToId($default_options['name']);
                    if(!in_array($default_options['id'], $ids))
                    {
                        $ids[] = $default_options['id'];
                    }
                    //unset($value['id']);    
                }

                $default_options['VALUE'] = $value['value'] ?? '';
                //unset($value['value']);

                //unset($value['options']);
                //unset($value['element']);

                //Lets register other keys other than the defaults
                foreach($value as $k=>$v)
                {
                    if(in_array($k, ['element', 'name', 'id', 'value', 'label', 'choices', 'attr']))
                    {
                        continue;
                    }
                    $default_options[$k] = $v;
                }

                $element = $this->byType($current_type, [], $default_options);
                $search = array_map(function($item){
                    return "{".$item."}"; 
                }, array_keys($default_options));        
                $element['elements'] = json_decode(str_replace($search, array_values($default_options), json_encode($element['elements'])), true);
                // if(in_array($current_type, $choices_tags))
                // {
                //     //$current_data = $value['options'];
                //     $datakey = isset($current_data[self::BODY_DATA]) ? $current_type.$key : self::BODY_DATA;
                //     $current_data[$datakey] = $value['choices'];
                //     if(self::isAssociative($element['elements']))
                //     {
                //         $element['elements'][$cDatakey] = $datakey;
                //     }
                //     else
                //     {
                //         $datakey['elements'][0][$cDatakey] = $datakey;
                //     }
                // }
                //REVISION: the above filter will miss the choices tags with another type name such as 'multiselect'
                if(isset($value['choices']))
                {
                    $datakey = isset($current_data[self::BODY_DATA]) ? $current_type.$key : self::BODY_DATA;
                    $current_data[$datakey] = $value['choices'];
                    if(self::isAssociative($element['elements']))
                    {
                        $element['elements'][$cDatakey] = $datakey;
                    }
                    else
                    {
                        $datakey['elements'][0][$cDatakey] = $datakey;
                    }                    
                }
                if($current_type=='hidden')
                {
                    $elements[self::VAR_CONTENT][] = $element['elements'];
                    continue;
                }
                $containerTemplate = $options['containerTemplate'] ?? 'container';
                $container = $this->getTemplate($containerTemplate);
                $container = json_decode(str_replace('{label}', $label_value, json_encode($container)), true);
                array_walk_recursive($container, function(&$value, $key, $element) {
                    if($value=='{{element}}')
                    {
                        $value = $element;  
                    }
                }, $element['elements']);
                if(isset($value['attr']) && count($value['attr']))
                {
                    $container = $this->applyAttributes($container, $value['attr']);
                } 
                $elements[self::VAR_CONTENT][] = $container;
            }
            if(!$isSubmitExist)
            {
                $button = $this->byType('submit', [], ['VALUE'=>'Submit']);
                $button['elements'] = json_decode(str_replace('{VALUE}', 'Submit', json_encode($button['elements'])), true);
                $elements[self::VAR_CONTENT][] = $button['elements'];
            }
            return ['elements'=>$elements, 'data'=>$current_data, 'options'=>$options];
        }  

        if(in_array($type, self::INPUT_TYPES) || in_array($type, self::BUTTON_TYPES)) 
        {
            $patternType = in_array($type, self::INPUT_TYPES) ? (isset($this->default_template[$type]) ? $type : 'input') : 'button';
        }

        $elements = $this->getTemplate($patternType);

        if($patternType == 'input' || $patternType == 'button')
        {
            $elements['type'] = $type;
        }

        if($options[self::VAR_TYPE]=='range')
        {
            $elements['class'] = 'form-range';            
        }
        if($type="accordion" || $type=='tab')
        {
            $options['id'] = $options['id'] ?? $type.rand(100,900);
        }
        if(isset($options['alwaysOpen']))
        {
            if($type=='accordion' && $options['alwaysOpen'])
            {
                if(isset($elements[0][self::VAR_CONTENT_PATTERN][0][self::VAR_CONTENT][1]['data-bs-parent']))
                {
                    unset($elements[0][self::VAR_CONTENT_PATTERN][0][self::VAR_CONTENT][1]['data-bs-parent']);
                }    
            }
            if($type=='nestedList' && !$options['alwaysOpen'])
            {
                $options['isAccordion'] = 'accordion';
            }
        }

        //$elements = json_decode(str_replace($search, array_values($options), json_encode($elements)), true);

        return ['elements'=>$elements, 'data'=>$data, 'options'=>$options];
    }
    
    /**
     * convert
     *
     * @param  array $elements
     * @param  array $data
     * @param  array $options
     * @return object
     */
    private function convert(array $elements, array $data = [], array $options = [], bool $placeInContainer = true)
    {
        if(self::isAssociative($elements))
        {
            $elements = [$elements];
        }

        if(!isset($options['hideIfNoData']))
        {
            $options = array_merge($this->default_options, $options);
        }

        if(count($options))
        {
            foreach($options as $oKey=>$oVal)
            {
                if(is_string($oVal))
                {
                    $oVal = self::interpret($oVal, $options);
                }   
                $options[$oKey] = $oVal; 
            }    
        } 

        $cType = self::VAR_TYPE;
        if(isset($options[$cType]) && isset($options['activeSection']) && in_array($options[$cType], ['accordion','tab','nestedList']))
        {
            if(isset($data[self::BODY_DATA]))
            {
                foreach($data as $key=>$value)
                {
                    $data[$key] = self::dataActivator($options[$cType], $value, $options['activeSection'], $options['alwaysOpen']);
                }
            }
            else
            {
                $data = self::dataActivator($options[$cType], $data, $options['activeSection'], $options['alwaysOpen']);
            }
        }

        $options['parentData'] = $data;
        $options['parentPattern'] = $elements;

        $parse_result = $this->parse($elements, $data, $options, $this->html_mode);

        if($this->directMarkeredReplacement)
        {
            $parse_result = $this->placeInContainer($parse_result);
        }
        return $this->addData($parse_result);
    }
    
    /**
     * applyAttributes
     *
     * @param  array $elements
     * @param  array $targettedAttributes
     * @return array
     */
    private function applyAttributes(array $elements, array $targettedAttributes)
    {
        $key = key($targettedAttributes);
        if(!is_array($targettedAttributes[$key]))
        {
            if(self::isAssociative($elements))
            {
                return array_merge($elements, $targettedAttributes);
            }
            else
            {
                $elements[0] = array_merge($elements[0], $targettedAttributes);
                return $elements;
            }

        }
        $attributeKeys = array_keys($targettedAttributes);
        $single = false;

        if(self::isAssociative($elements))
        {
            $elements = [$elements];
            $single = true;
        }
        foreach ($elements as $n => $element) 
        {
            if(is_string($element))
            {
                continue;
            }
            $tag = $element[self::VAR_TAG] ?? 'div';
            if(count(array_intersect(array_keys($element), [self::VAR_CONTENT, self::VAR_CONTENT_PATTERN, 'class', 'id', 'tag'])))
            {
                foreach ($element as $attrKey => $attrValue) {
                    if(!in_array($attrKey, [self::VAR_CONTENT, self::VAR_CONTENT_PATTERN, 'class', 'id', 'tag']))
                    {
                        continue;                        
                    }
                    if(in_array($attrKey, [self::VAR_CONTENT, self::VAR_CONTENT_PATTERN]))
                    {                
                        if(count($attrValue))
                        {
                            $elements[$n][$attrKey] = $this->applyAttributes($attrValue, $targettedAttributes);
                        }
                    }
                    if(in_array($attrKey, ['class', 'id', self::VAR_TAG]))
                    {
                        //element input should be named by its type in targettedAttributes
                        if($tag=='input')
                        {
                            $tag = $element['type'] ?? 'text';
                        }                        
                        if(in_array($attrKey, ['class', 'id']))
                        {
                            $separator = $attrKey=='class' ? '.' : '#';
                        }
                        $keys = explode(" ", $attrValue);
                        $searchKeys = [];
                        foreach ($keys as $key) {
                            $searchKeys[] = "$tag";
                            if(in_array($attrKey, ['class', 'id']))
                            {
                                $searchKeys[] = "{$tag}{$separator}{$key}";
                                $searchKeys[] = "{$separator}{$key}";    
                            }
                        }
                        $intersect = array_intersect($searchKeys, $attributeKeys);
                        if(count($intersect))
                        {
                            foreach($intersect as $searchKey)
                            {
                                $elements[$n] = array_merge($element, $targettedAttributes[$searchKey]);
                            }
                        }
                    }                
                }    
            }
        }
        return $single ? $elements[0] : $elements;
    }
    
    function setHtmlMode(bool $setting = true)
    {
        $this->html_mode = $setting;
    }

    /**
     * setTemplate
     *
     * @param  array        $htmlArray
     * @return object
     */
    function setTemplates(array $htmlArray)
    {
        if(count($htmlArray))
        {
            $this->default_template = array_merge($this->default_template, $htmlArray);
        };
        return $this;
    }

    /**
     * setTemplates
     *
     * @param  string        $type
     * @param  array        $htmlArray
     * @return object
     */
    function setTemplate(string $type, array $htmlArray)
    {
        $this->default_template[$type] = $htmlArray;
        return $this;
    }   

    /**
     * getTemplate
     *
     * @param  string       $type
     * @return array
     */
    function getTemplate(string $type)
    {
        return $this->default_template[$type] ?? [];
    }    
        
    /**
     * fromType
     *
     * @param  string $type
     * @param  array $targettedAttributes
     * @param  array $data
     * @param  array $options
     * @return object
     */
    function fromType(string $type, array $targettedAttributes = [], array $data=[], $options=[])
    {
        $htmlArray = $this->byType($type, $data, $options);

        if(!count($htmlArray))
        {
            return $this;
        }

        if(count($targettedAttributes))
        {
            $special_attr = 'marker';
            if(in_array($special_attr, array_keys($targettedAttributes)))
            {
                if($this->isAssociative($htmlArray['elements']))
                {
                    $htmlArray['elements'][$special_attr] = $targettedAttributes[$special_attr];
                }
                else
                {
                    //elements will be contained in single container
                    if(!isset($htmlArray['elements']['class']))
                    {
                        $htmlArray['elements'] = [
                            'tag'=>'div',
                            'class'=>'',
                            $special_attr=>$targettedAttributes[$special_attr],
                            'content'=>$htmlArray['elements']
                        ];    
                    }
                }
                            
            }
            $applyAttribute = true;
            $key = key($targettedAttributes);
            if(!is_array($targettedAttributes[$key]))
            {
                //custom attribute like 'marker' will be difficult to apply, especially in custom elements like tab, accordion, nestedList, because HTML dont have that tag
                //so we will add the custom attribute in the first-tier element
                $attrKeys = array_keys($targettedAttributes);
                $special_attributes = [$special_attr];
                $intersect = array_intersect($attrKeys, $special_attributes);

                if(count($intersect)==count($targettedAttributes))
                {
                    $applyAttribute = false;
                }
                $options['withFormTag'] = $options['withFormTag'] ?? true;
                $thisType = $this->typeToTag($type, $htmlArray['elements']);
                $thisType = $type=='formTemplate' && $options['withFormTag'] ? 'div.'.self::FORM_CONTAINER_CLASS : $thisType;
                $targettedAttributes = [$thisType=>$targettedAttributes];

            }    
            if($applyAttribute)
            {
                $htmlArray['elements'] = $this->applyAttributes($htmlArray['elements'], $targettedAttributes);
            }
            
        }
        return $this->convert($htmlArray['elements'], $htmlArray['data'], $htmlArray['options']);
    }   

    /**
     * fromArray
     *
     * @param  array $elements
     * @param  array $contentData
     * @param  array $options
     * @return object
     */
    function fromArray(array $elements, array $contentData = [], array $options = [])
    {
        return $this->convert($elements, $contentData, $options);
    }

    function fromData(array $elementData)
    {
        $this->directMarkeredReplacement = false;
        $components = ['data', 'attr', 'opts'];
        $result = [];
        $elementString = json_encode($elementData);
        //Search all text inside double curly brackets
        preg_match_all('/{{(.*?)}}/', $elementString, $output);
        $markers = $output[1];
        foreach ($elementData as $n => $element) {

            if(is_string($element))
            {
                $result[$n] = $element;
                continue;
            }
            
            foreach ($components as $comp) {
                $$comp = $element[$comp] ?? [];
            }

            if(in_array($n, $markers))
            {
                $attr['marker'] = $n;
            }
            if(strpos($n, self::TEMPLATE_MARK) !== FALSE)
            {
                $e = explode(self::TEMPLATE_MARK, $n);
                switch ($e[0]) {
                    case 'table':
                        $data = $this->tableDataConverter($data[self::BODY_DATA], $data['header']);
                        break;
                    case 'tab':
                    case 'accordion':
                        $d = $this->argFormatter($e[0], $data[self::BODY_DATA], $data['header'], $opts['activeSection']??'', $opts);
                        $data = $d['data'];
                        $opts = $d['options'];
                        break;
                }
                $this->fromType($e[0], $attr, $data, $opts);
            }
            else
            {
                $e = explode(self::REGULAR_MARK, $n);
                $attr['tag'] = $e[0];
                $this->fromArray($attr, $data, $opts);
            }
        }
        return $this;
    }
    
    /**
     * fromString
     *
     * You can create a pseudo array in string to form an array that is converted into HTML.
     * Things to note in creating this pseudo array string:
     * 1. String separated by line
     * 2. Each array begins with character '>' which represented an element, if nested (content of the element), write '>' as many as the related level, e.g. nested from an array level 1 ('>') will begins with '>>'
     * 3. Element tag ends with ':'
     * 4. Attribute is written like usual attribut in HTML, separated by line
     * 5. Text is directly written in related line 
     * 
     * Example:
        >
        div:
        class=test blaa
        id=dodol-kali
        >>
            TESTING TESTING
            div:
            class=one
            >>>
                TEWEWEW
                span:
                class=one-half
                >>>> 
                    GALEONG
            >>>
                div:
                class=two 
                id=bla
                >>>>
                    Aku adalah insan yang tak punya
        >
        div:
        class=test blaa2 
        id=dodol-kali2
        >>
            div:
            class=two2 
            id=bla2
            >>>
                Aku adalah insan yang tak punya 2
     * @param  string $arrayString
     * @return object
     */
    function fromString(string $arrayString, array $contentData = [], array $options = [])
    {
        $lines = explode("\n", $arrayString);
        $result = [];
        $tag = '';
        $mode = '';
        $previous_mode = '';
        $elementKey = -1;
        $parentKey = -1;
        $level = 0;
        $levelParents = [];
        $prevLevel = 0;
        $marker='';
        $prevContent = '';
        foreach ($lines as $line) {
            $line = trim($line);
            if(!$line)
            {
                continue;
            }
            $previous_mode = $mode;
            $isTag = (strpos($line, ':') !== FALSE);
            $isAttr = (strpos($line, '=') !== FALSE);
            $isContent = (strpos($line, '>') !== FALSE);
            $isContentPattern = (strpos($line, '*') !== FALSE);        
            if($isContent || $isContentPattern)
            {
                $prevLevel = $level;
                $level = strlen($line);
                $mode = $isContent ? 'content' : 'contentPattern';
                $prevContent = $mode;
                if($level<=1)
                {
                    $levelParents = [];
                    continue;
                }
            }
            if($isTag)
            {
                $tag = substr($line,0,strlen($line)-1);
                $mode = in_array($tag, self::SELF_CLOSING_TAG) ? 'stag' : 'rtag';
                $elementKey++;
                if($level>1)
                {
                    if($marker)
                    {
                        $levelParents[$level] = $levelParents[$level] ?? $marker;
                    }
                    $marker = "element_{$level}_{$elementKey}";
                    $check = $prevLevel > $level && in_array($level, array_keys($levelParents)) && count($levelParents);
                    if($check)
                    {
                        $pKey = $parentKey;
                        $levelMarker = $levelParents[$level] ?? '';
                        if($levelMarker)
                        {
                            foreach ($result as $key => $value) 
                            {
                                $m = $value['marker']??'';
                                if($m==$levelMarker)
                                {
                                    $pKey = $key;
                                    break;
                                }
                            }
                        }
                        $result[$pKey][$prevContent][] = "{{{$marker}}}";
                    }
                    else
                    {
                        $result[$parentKey][$prevContent][] = "{{{$marker}}}";
                    }
                    array_unshift($result, ['tag'=>$tag, 'marker'=>$marker]);//
                    $parentKey = 0;
                }
                else
                {
                    $result[$elementKey] = ['tag'=>$tag];
                    $parentKey = $elementKey;//
                }
            }
            if($isAttr)
            {
                $isValid = in_array($previous_mode, ['stag','rtag','sattr','rattr']);
                if(!$isValid)
                {
                    continue;
                }
                $mode = $previous_mode=='stag' ? 'sattr' : 'rattr';
                $tmp = explode('=', $line);
                $result[$parentKey][$tmp[0]] = $tmp[1];
            }
            if(!$isTag && !$isAttr && !$isContent && !$isContentPattern)
            {
                $isInvalid = in_array($previous_mode, ['rtag','rattr']);
                if($isInvalid)
                {
                    continue;
                }
                $mode = 'text';
                $elementKey++;
                if($level>1)
                {
                    $result[$parentKey][$prevContent][] = $line;                
                    continue;
                }
                $result[$elementKey] = $line;
            }
        }        
        return $this->fromArray($result, $contentData, $options);
    }

    
    // ALIASES
        
    /**
     * formTemplate
     *
     * $formAttribute = ['action'=>'someUrl',...etc]
     * $elementData is each element to contain in the form
     * Each form element follow this format
     * [
     *  'element'=>input/select/checkbox/etc, *required
     *  'name'=>'someName', 
     *  'id'=>'someId',
     *  'label'=>'Some Label',
     *  'value'=>'someValue', <- will be converted to selectedValue for choice elements like select, checkbox
     *  'options'=>someArray  <- only for choice elements
     * ];
     * 
     * Each element will create a form control, consisting label and the element itself, except for hidden input
     * 
     * If you set $options['plainText'] to true, it will display the value in plain text, including choices elements
     * 
     * @param  array $formAttribute
     * @param  array $elementData
     * @return object
     */
    function formTemplate(array $formAttribute = [], array $elementData = [], $options = [])
    {
        return $this->fromType(__FUNCTION__, $formAttribute, $elementData, $options);
    }
    
    /**
     * form
     *
     * @param  array $targettedAttributes
     * @param  array $formCfg
     * @return object
     */
    function form(array $targettedAttributes = [], array $options = [])
    {
        return $this->fromType(__FUNCTION__, $targettedAttributes, [], $options);
    }

    // * INPUT ELEMENTS
    //   Method input() is special for input type=text, the other is like its own function name.

    /**
     * input
     *
     * @param  string $name
     * @param  string $value
     * @param  array $targettedAttributes
     * @param  array $options
     * @return object
     */
    function input($name, string $value = '', array $targettedAttributes = [], array $options = []) 
    {
        $options['VALUE'] = $value;      
        $options['name'] = $name;  
        return $this->fromType('text', $targettedAttributes, [], $options);
    }
    function hidden($name, string $value = '', array $options = [])
    {
        $options['VALUE'] = $value;
        $options['name'] = $name;
        return $this->fromType(__FUNCTION__, [], [], $options);
    }   
    function password($name, string $value = '', array $targettedAttributes = [], array $options = [])
    {
        $options['VALUE'] = $value;
        $options['name'] = $name;
        return $this->fromType(__FUNCTION__, $targettedAttributes, [], $options);
    }
    function email($name, string $value = '', array $targettedAttributes = [], array $options = [])
    {
        $options['VALUE'] = $value;
        $options['name'] = $name;
        return $this->fromType(__FUNCTION__, $targettedAttributes, [], $options);
    }
    function search($name, string $value = '', array $targettedAttributes = [], array $options = [])
    {
        $options['VALUE'] = $value;
        $options['name'] = $name;
        return $this->fromType(__FUNCTION__, $targettedAttributes, [], $options);
    }
    function tel($name, string $value = '', array $targettedAttributes = [], array $options = [])
    {
        $options['VALUE'] = $value;
        $options['name'] = $name;
        return $this->fromType(__FUNCTION__, $targettedAttributes, [], $options);
    }
    function url($name, string $value = '', array $targettedAttributes = [], array $options = [])
    {
        $options['VALUE'] = $value;
        $options['name'] = $name;
        return $this->fromType(__FUNCTION__, $targettedAttributes, [], $options);
    }
    function number($name, string $value = '', array $targettedAttributes = [], array $options = [])
    {
        $options['VALUE'] = $value;
        $options['name'] = $name;
        return $this->fromType(__FUNCTION__, $targettedAttributes, [], $options);
    }
    function range($name, string $value = '', array $targettedAttributes = [], array $options = [])
    {
        $options['VALUE'] = $value;
        $options['name'] = $name;
        return $this->fromType(__FUNCTION__, $targettedAttributes, [], $options);
    }
    function month($name, string $value = '', array $targettedAttributes = [], array $options = [])
    {
        $options['VALUE'] = $value;
        $options['name'] = $name;
        return $this->fromType(__FUNCTION__, $targettedAttributes, [], $options);
    }
    function time($name, string $value = '', array $targettedAttributes = [], array $options = [])
    {
        $options['VALUE'] = $value;
        $options['name'] = $name;
        return $this->fromType(__FUNCTION__, $targettedAttributes, [], $options);
    }
    function week($name, string $value = '', array $targettedAttributes = [], array $options = [])
    {
        $options['VALUE'] = $value;
        $options['name'] = $name;
        return $this->fromType(__FUNCTION__, $targettedAttributes, [], $options);
    }
    function date($name, string $value = '', array $targettedAttributes = [], array $options = [])
    {
        $options['VALUE'] = $value;
        $options['name'] = $name;
        return $this->fromType(__FUNCTION__, $targettedAttributes, [], $options);
    }
    function color($name, string $value = '', array $targettedAttributes = [], array $options = [])
    {
        $options['VALUE'] = $value;
        $options['name'] = $name;
        return $this->fromType(__FUNCTION__, $targettedAttributes, [], $options);
    }
    function datetime_local($name, string $value = '', array $targettedAttributes = [], array $options = [])
    {
        $options['VALUE'] = $value;
        $options['name'] = $name;
        return $this->fromType('datetime-local', $targettedAttributes, [], $options);
    }
    function file($name, string $value = '', array $targettedAttributes = [], array $options = [])
    {
        // $options['VALUE'] = $value;
        $options['name'] = $name;
        return $this->fromType(__FUNCTION__, $targettedAttributes, [], $options);
    }
    function files($name, string $value = '', array $targettedAttributes = [], array $options = [])
    {
        // $options['VALUE'] = $value;
        $options['name'] = $name;
        $targettedAttributes['multiple'] = true;
        return $this->fromType('file', $targettedAttributes, [], $options);
    }    
    function button(string $text = '', array $targettedAttributes = [])
    {
        $options['VALUE'] = $text;
        return $this->fromType(__FUNCTION__, $targettedAttributes, [], $options);
    }
    function submit(string $text = '', array $targettedAttributes = [])
    {
        $options['VALUE'] = $text;
        return $this->fromType(__FUNCTION__, $targettedAttributes, [], $options);
    }
    function reset(string $text = '', array $targettedAttributes = [])
    {
        $options['VALUE'] = $text;
        return $this->fromType(__FUNCTION__, $targettedAttributes, [], $options);
    }   

    // * END OF INPUT ELEMENTS

    function textarea($name, string $value = '', array $targettedAttributes = [], array $options = [])
    {
        $options['VALUE'] = $value;
        $options['name'] = $name;
        return $this->fromType(__FUNCTION__, $targettedAttributes, [], $options);
    }

    function label(string $text = '', array $targettedAttributes = [])
    {
        $options['VALUE'] = $text;
        return $this->fromType(__FUNCTION__, $targettedAttributes);
    }      
    
    // * ELEMENTS WITH CHOICES
    
    function select($name, $value = '', $choices = [], array $targettedAttributes = [], $options = [])
    {
        $options['VALUE'] = $value;
        $options['name'] = $name;
        return $this->fromType(__FUNCTION__, $targettedAttributes, $choices, $options);
    }
    function checkbox($name, $value = '', $choices = [], array $targettedAttributes = [], $options = [])
    {
        $options['VALUE'] = $value;
        $options['name'] = $name;        
        return $this->fromType(__FUNCTION__, $targettedAttributes, $choices, $options);
    }    
    function radio($name, $value = '', $choices = [], array $targettedAttributes = [], $options = [])
    {
        $options['VALUE'] = $value;
        $options['name'] = $name;        
        return $this->fromType(__FUNCTION__, $targettedAttributes, $choices, $options);
    }  
    function switch($name, $value = '', $choices = [], array $targettedAttributes = [], $options = [])
    {
        $options['VALUE'] = $value;
        $options['name'] = $name;
        return $this->fromType(__FUNCTION__, $targettedAttributes, $choices, $options);
    }      
         
    // * END OF ELEMENTS WITH CHOICES
    
    private function tableDataConverter(array $data, array $headers = [])
    {
        $result = [];
        foreach ($data as $row_data) 
        {
            $result[] = ['',[],$row_data];
        }

        $data = $result;

        $finalData = [self::BODY_DATA=>$data];
        if(count($headers))
        {
            $finalData['header'] = $headers;
        }
        else
        {
            //If no header provided, then the column name will be used as header;
            $temp = array_map(function($item){
                $item = ucwords(str_replace(['_','-'],[' ', ' '], $item));
                return $item;
            }, array_keys($data[0][2]));
            $finalData['header'] = array_combine($temp, $temp);
        }  
        return $finalData;      
    }
    
    /**
     * table
     * This function assume that $data provided is returned from database which has differen format with the nested form
     * 
     * $headers format:
     * ['Header 1', 'Header 2', 'Header 3']
     * 
     * $data format:
     * [
     *  ['a'=>'A1', 'b'=>'B1', 'c'=>'C1'],
     *  ['a'=>'A2', 'b'=>'B2', 'c'=>'C2'],
     *  ['a'=>'A3', 'b'=>'B3', 'c'=>'C3'],
     *  ['a'=>'A4', 'b'=>'B4', 'c'=>'C4'],
     * ]; 
     * 
     * OR (if you're sure you fill the $headers)
     * [
     *  ['A1', 'B1', 'C1'],
     *  ['A2', 'B2', 'C2'],
     *  ['A3', 'B3', 'C3'],
     *  ['A4', 'B4', 'C4'],
     * ];  
     * 
     * 
     * @param  array $htmlArray
     * @param  array $data
     * @param  bool $hasHeader
     * @return object
     */
    function table(array $data, array $headers = [], array $targettedAttributes = [])
    {
        $finalData = $this->tableDataConverter($data, $headers);
        return $this->fromType(__FUNCTION__, $targettedAttributes, $finalData);                
    }

    private function argFormatter(string $type, array $data, array $headers = [], string $activeSection = '', $options = [])
    {
        $data_keys = array_keys($data);
        if(!self::isAssociative($data))
        {
            $rand = rand(10,99);
            $data_keys = array_map(function($item) use($rand, $type){
                return "{$type}{$rand}_{$item}";
            }, $data_keys);
            $data = array_combine($data_keys, $data);
        }
        $headers = count($headers) ? $headers : $data_keys;
        $headers = count($data)!=count($headers) ? array_chunk($headers, count($data)) : $headers;
        $headers = array_combine($data_keys, $headers);

        if($activeSection && is_numeric($activeSection))
        {
            $activeSection = $data_keys[$activeSection];
        }

        if($type == 'tab')
        {
            $final_data = [
                'header'=>$headers,
                self::BODY_DATA=>$data
            ];            
            $options['activeSection'] = $activeSection ? $activeSection : key($final_data[self::BODY_DATA]);
        }
        else
        {
            $final_data = [];
            foreach ($data as $key => $value) 
            {
                $final_data[$key] = [$headers[$key], [], [$key=>$value]];
            }    
            $options['activeSection'] = $activeSection ? $activeSection : key($final_data);            
        }

        return ['data'=>$final_data, 'options'=>$options];
    }
    
    /**
     * accordion
     *
     * $headers = ['Header 1', 'Header 2', 'Header 3'];
     * $data = ['Body 1', 'Body 2', 'Body 3'];
     * 
     * The same format applies to tab
     * 
     * @param  array $data
     * @param  array $headers
     * @param  string $activeSection
     * @param  array $options
     * @return object
     */
    function accordion(array $data, array $headers = [], string $activeSection = '', $options = [])
    {
        $d = $this->argFormatter(__FUNCTION__, $data, $headers, $activeSection, $options);
        return $this->fromType(__FUNCTION__, [], $d['data'], $d['options']);
    }

    function tab(array $data, array $headers = [], string $activeSection = '', $options = [])
    {
        $d = $this->argFormatter(__FUNCTION__, $data, $headers, $activeSection, $options);
        return $this->fromType(__FUNCTION__, [], $d['data'], $d['options']);
    } 
    /**
     * nestedList
     *
     * $data format :
     * $array = 
     * [
     *  key1=>[value1, [], [dataChildren1]],
     *  key2=>[value2, [], [dataChildren2]],
     * ]
     * dataChildren should follow the same format.
     * The whole format is like this:
     * [
     *  key1=>[value1, [], [key1a=>[value1a], key1b=>[value1b]]],
     *  key2=>[value2, [], [key2a=>[value2a], key2b=>[value2b]]],
     * ]
     * 
     * Data from db can be converted into this format with method dbToNested
     * 
     * @param  array $data
     * @param  array $targettedAttributes
     * @param  array $options
     * @return object
     */
    function nestedList(array $data, array $targettedAttributes = [], $options = [])
    {
        return $this->fromType(__FUNCTION__, $targettedAttributes, $data, $options);
    }

    function script(string $script)
    {
        return $this->fromType(__FUNCTION__, [], [], ['VALUE'=>$script]);
    }

    function style(string $style)
    {
        return $this->fromType(__FUNCTION__, [], [], ['VALUE'=>$style]);
    }

    // END OF ALIASES

    /**
     * render
     *
     * @return string
     */
    function render()
    {
        if($this->html_mode)
        {
            $html = implode("", $this->html_string);
            $this->html_string = [];
        }
        else
        {
            $html = $this->parse($this->html_array);
            $this->html_array = [];    
        }
        if(!$this->directMarkeredReplacement)
        {
            //Intra markers
            foreach ($this->markeredElements as $n => $value) 
            {
                $this->markeredElements[$n] = $this->placeInContainer($value);
            }
            $html = $this->placeInContainer($html);
            $this->directMarkeredReplacement = true;
        }        
        //Search and delete the marker attribute
        preg_match_all('/(\smarker.*?)[\s|>]/', $html, $output);
        if(count($output[1]))
        {
            $replacer = array_fill(0, count($output[0]), "");
            $html = str_replace($output[1], $replacer, $html);
        }

        $this->markeredElements = [];
        return $html;
    }
        
    /**
     * htmlToArray
     *
     * @param  string $html
     * @return array
     */
    function htmlToArray(string $html)
    {
        $dom = new DOMDocument();
        $dom->loadHTML($html);
        return self::elementToArray($dom->documentElement);
    }
    
    /**
     * exportToArray
     *
     * @return array
     */
    function exportToArray()
    {
        return $this->html_mode ? $this->htmlToArray(implode("", $this->html_string)) : $this->html_array;
    }
    
    /**
     * exportToJson
     *
     * @return string
     */
    function exportToJson()
    {
        return json_encode($this->exportToArray());
    }

    /**
     * dataNester
     * Convert data in database format into nested
     * 
     * $normal_data_from_db = 
     * [
     *  ['id'=>1, 'menu'=>'Title 1', 'link'=>'link1', 'parent_id'=>0],
     *  ['id'=>2, 'menu'=>'Title 2', 'link'=>'link2', 'parent_id'=>1],
     *  ['id'=>3, 'menu'=>'Title 3', 'link'=>'link3', 'parent_id'=>0],
     *  ['id'=>4, 'menu'=>'Title 4', 'link'=>'link4', 'parent_id'=>1]        
     * ];
     * 
     * Here's the nested format:
     * $array = 
     * [
     *  key1=>[value1, [], [dataChildren1]],
     *  key2=>[value2, [], [dataChildren2]],
     * ]
     * 
     * The middle array is to be used later as variable for the element
     * 
     * If we set $colRef='id', $colValue='menu', $colPid='parent_id', $colKey='link',
     * the output will be something like this
     * [
     *  'link1'=>['Title 1', [], [
     *              'link2'=>['Title 2',[],[]],
     *              'link4'=>['Title 4',[],[]], 
     *          ]],
     *  'link3'=>['Title 3', [], []]
     * ] 
     * link1 (having id=1) has two children because link2 and link4 has parent_id = 1
     * link3 has no child because no record refer to its id
     * 
     * @param  array $data      array to convert
     * @param  string $colRef   column to compared to $pid (e.g. id)
     * @param  string $colValue column to take as value in final array (e.g. menu)
     * @param  string $colPid   column having the info about the parent id (e.g. parent_id)
     * @param  int $pid       the value of parent to start looking, usually started with 0
     * @param  string $colKey   column to be the key (e.g. link)
     * @return array
     */
    function dbToNested($data, $colRef, $colValue, $colPid, $pid=0, $colKey='')
    {
        $result = [];
        $dataFiltered = $data;
        $n = 0;
        foreach ($data as $key => $value) 
        {
            if($value[$colRef]==$pid)
            {
                unset($dataFiltered[$key]);
                $currentPid = $value[$colPid];
                $temp = $this->dbToNested($dataFiltered, $colRef, $colValue, $colPid, $currentPid, $colKey);
                $currentKey = $colKey ? $value[$colKey] : $key;
                $result[$n][$currentKey] = [$value[$colValue], [], $temp];
                $n++;
            }
        }

        return $result;
    }
}