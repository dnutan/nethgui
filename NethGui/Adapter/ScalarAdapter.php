<?php
/**
 * @package Adapter
 */


/**
 * Scalar adapter maps a scalar value to a key or prop value through a Serializer.
 *
 * @package Adapter
 */
class NethGui_Adapter_ScalarAdapter implements NethGui_Adapter_AdapterInterface
{

    protected $modified;
    protected $value;
    /**
     *
     * @var NethGui_Serializer_SerializerInterface
     */
    private $serializer;

    public function __construct(NethGui_Serializer_SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function delete()
    {
        $this->set(NULL);
    }

    public function get()
    {
        if (is_null($this->modified)) {
            $this->modified = FALSE;
            $this->value = $this->serializer->read();            
        }
        return $this->value;
    }

    public function set($value)
    {
        if (is_null($this->modified)) {
            $this->modified = FALSE;
            $this->value = $this->serializer->read();
        }

        if ($this->value !== $value) {
            $this->value = $value;
            $this->modified = TRUE;
        }
    }

    public function isModified()
    {
        return $this->modified === TRUE;
    }

    public function save()
    {
        if ( ! $this->isModified()) {
            return 0;
        }

        $this->serializer->write($this->value);
        $this->modified = FALSE;
        
        return 1;
    }

    public function __toString()
    {
        return strval($this->get());
    }

}