<?php
/**
 * Created by PhpStorm.
 * User: se7en
 * Date: 2020/3/12
 * Time: 21:55
 */

namespace liuqiandev\thinkphp_facade_generator;

use think\console\command\Make;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\Exception;

class AppFacade extends MessageAbstract
{
    protected $type = "appFacade";
    protected $annotation = [];
    protected $className;
    protected $importObject;
    protected $annotationStrCache;
    protected $facadeName;
    protected function configure()
    {
        parent::configure();
        $this->setName('make:appFacade')
            ->addOption('common', null, Option::VALUE_OPTIONAL, '[Default]facade class will build in common/facade path')
            ->addOption('self', null, Option::VALUE_OPTIONAL, 'facade class will build in %self%/facade path')
            ->setDescription('Create a new appFacade class');
    }
    protected function execute(Input $input, Output $output)
    {
        $this->className = $this->getClassName($input->getArgument('name'));

        if($input->hasOption('self')){
            $pathname = $this->getPathName($this->className);
            $dirname = dirname($pathname).DIRECTORY_SEPARATOR.'facade';
        }else{
            $dirname = app()->getAppPath().'common'.DIRECTORY_SEPARATOR.'facade';
        }


        $classNameArray = explode('\\',$this->className);
        $this->facadeName = array_pop($classNameArray);

        $pathname =$dirname.DIRECTORY_SEPARATOR.$this->facadeName.'.php';
        $pathname = str_replace('/', '\\', $pathname);
        if (is_file($pathname)) {
            $output->writeln('<error>' . $this->type . ' already exists!</error>');
            return false;
        }
        $this->getClassAnnotation();
        if (!is_dir(dirname($pathname))) {
            mkdir(dirname($pathname), 0755, true);
        }
        file_put_contents($pathname, self::buildClass($pathname));

        $output->writeln('<info>' . $pathname . ' created successfully.</info>');

    }

    protected function buildClass($name)
    {
        $stub = file_get_contents($this->getStub());

        $name = str_replace(app()->getAppPath(), '', $name);

        $name = app()->getNamespace().'\\'.$name;
        $namespace = trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');

        $className = str_replace($namespace . '\\', '', $name);

        $className = str_replace('.php', '', $className);
        if(is_array($this->importObject)&&count($this->importObject)>0){
            $importClass = implode(';'.PHP_EOL,$this->importObject);
            $importClass .=';'.PHP_EOL.PHP_EOL;
        }else{
            $importClass = '';
        }


        $annotation = '/**'.PHP_EOL;
        $annotation .=' * @see \\'.$this->className.PHP_EOL;
        $annotation .=' * @mixin \\'.$this->className.PHP_EOL;
        $annotation .=implode(PHP_EOL,$this->annotation);
        $annotation .=PHP_EOL;
        $annotation .=' */';

        $class = '\\'.$this->className.'::class';
        if(substr($class,0,1)!=='\\'){
            $class = '\\'.$class;
        };
        return str_replace(['{%namespace%}','{%importClass%}','{%annotation%}', '{%className%}',  '{%class%}'], [
            $namespace,
            $importClass,
            $annotation,
            $className,
            $class
        ], $stub);

    }

    protected function getStub()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'stub' . DIRECTORY_SEPARATOR . 'appFacade.stub';
    }

    protected function getClassAnnotation()
    {
        if(class_exists($this->className)===false){
            throw new Exception($this->className.' not exist!');
        }
        try{
            $reflectionClass = new \ReflectionClass($this->className);
            foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method){
                $this->getAnnotationOfMethod($method);
            }
        }catch (\Exception $e){
            throw new Exception($e->getMessage());
        }
        if(count($this->annotation)===0){
            throw new Exception('玩我呢，一个public方法都没有要什么Facade');
        }

    }

    protected function getAnnotationOfMethod(\ReflectionMethod $method):void
    {
        if($method->isPublic()){
            $this->annotationStrCache =' * @method';
            $this->annotationStrCache .=' '.$this->getReturnType($method);
            $this->annotationStrCache .=' '.$method->getName();
            $this->annotationStrCache .=$this->getMethodParameters($method);
            $this->annotationStrCache .=' static';
            $this->annotationStrCache .=' '.$this->getMethodDesc($method);
            $this->annotation[] = $this->annotationStrCache;
        }

    }

    protected function getReturnType(\ReflectionMethod $method)
    {
        if($method->hasReturnType()){
            return $this->parseType($method->getReturnType());
        }
        return 'mixed';

    }
    protected function getMethodParameters(\ReflectionMethod $method)
    {
        if($method->getNumberOfParameters()===0){
            return '()';
        }
        $paramArr = [];
        foreach ($method->getParameters() as $key => $parameter){

            $paramArr[]=$this->parseMethodParameter($parameter,$key);
        }
        $param = implode(',',$paramArr);
        return '('.$param.')';
    }
    protected function parseType(\ReflectionNamedType $returnType,$parameter=false)
    {
        //php 内置返回类型 int string array float等
        if($returnType->isBuiltin()){
            return $returnType->getName();
        }
        $returnTypeName = $returnType->getName();
        //返回自身
        if($returnTypeName==='self'){
            return '\\'.$this->className;
        }
        //类的实例
        if(class_exists($returnTypeName)){
            $this->addImportObject($returnTypeName);
            $returnTypeArray =explode('\\',$returnTypeName);
            return array_pop($returnTypeArray);
        }
        return 'mixed';
    }
    protected function getMethodDesc(\ReflectionMethod $method)
    {
        if($method->getDocComment()===false){
            return null;
        }
        $docComment = $method->getDocComment();
        $matches = preg_match('/@(desc|description)(.*)\n/Su',$docComment,$desc);
        if($matches){
            return trim(array_pop($desc));
        }
        return null;
    }
    protected function parseMethodParameter(\ReflectionParameter $parameter,$key)
    {
        $parameterStr = '';
        if($parameter->hasType()){
            $parameterStr .= $key===0?'':' ';
            $parameterStr .=$this->parseType($parameter->getType(),true); ;
        }
        $parameterStr .=' $'.$parameter->getName();
        if($parameter->isDefaultValueAvailable()){
            $parameterStr .='='.$this->parseParameterDefaultValue($parameter->getDefaultValue());
        }
        return $parameterStr;
    }
    protected function parseParameterDefaultValue($defaultValue)
    {
        $valueType = gettype($defaultValue);
        switch (true){
            case $valueType==='integer':
                return (int)$defaultValue;
                break;
            case $valueType==='string':
                return (string)'\''.$defaultValue.'\'';
                break;
            case $valueType==='double':
                return (float)$defaultValue;
                break;
            case $valueType==='array':
                //此处太难处理，暂时只支持空数组
                return '[]';
                break;
            case $valueType==='boolean':
                //此处太难处理，暂时只支持空数组
                return $defaultValue?'true':'false';
                break;
            default:
                return 'null';
        }
    }
    protected function addImportObject(string $importObjectNameSpace):void
    {
        if(in_array($importObjectNameSpace,$this->importObject)===false){
            $this->importObject[] = 'Use '.$importObjectNameSpace;
        }

    }
}