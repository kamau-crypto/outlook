//
//Resolve the mutall error class, plus access to the application url
import * as schema from "./schema.js";
// 
//We need the library dts to support the parametarization of the 
//sever methods 
import * as library from "./library.js";
//
//Simplifies the windows equivalent fetch method with the following 
//behaviour.
//If the fetch was successful, we return the result; otherwise the fetch 
//fails with an exception.
//partcular static methods are specifed static:true....
//It returns the same as result as the method  in php 
export async function exec<
    // 
    //Get the type that represents...
    //...classes in the library namespace, organised as an object, e.g.,
    //{database:object, record:object, node:object }
    classes extends typeof library,
    //
    //...the class name as string inorder to comply with the formdata.append parameters i.e.,
    //string|blob
    class_name extends Extract<keyof classes, string>,
    //
    //...A class in the library namespace, e.g.,
    //class node { 
    //  export: (..args: any)=> any,
    //  prototype: new (...args:any)=>any
    //}
    $class extends classes[class_name],
    // 
    //... the constructor parameters without using the predefined construction parameter.
    //cargs extends $class extends new (...args: infer c) => any ? c : never,
    //Because the following  bit failed to work.
    cargs extends ConstructorParameters<$class>,
    //
    //...The  instance type of the constructor directly without using the predefined construction
    //instance extends $class extends new (...args: any) => infer r ? r : never,
    instance extends InstanceType<$class>,
    // 
    //...The object method must be a string.
    method_name extends Extract<keyof instance,string>,
    // 
    //...The object method
    method extends instance[method_name],
    //extends { (...args: any): any } ? instance[method_name] : never,
    // 
    //....the method arguments 
    //margs extends method extends (...args: infer p)=> any ? p[] : never,
    margs extends Parameters<method>,
    // 
    //...The return type 
    $return extends ReturnType<method>,
    //$return extends method extends  (...args: any[])=> infer r  ? r : any
    >(
        //
        //The class of the php class to execute.
        class_name: class_name,
        //
        cargs: cargs,
        //
        method_name: method_name,
        //
        margs: margs
    ): Promise<$return> {
    //
    //Call the non parametric form of exec
    return await exec_nonparam(class_name,method_name,margs,cargs);
 }
//
//
//Post the given file to the server at the given folder.
export async function post_file(
    file: Blob,
    path: string
): Promise<{ok: boolean, result: any, html: string}> {
    //
    //1. Create a form data object
    const formData = new FormData();
    //
    //2. Append the file to the form data object
    //
    //Attach the folder name where the file will go
    formData.append('path', path);
    //
    //Attach the actual file to the form data 
    formData.append("file", file);
    //     
    //4. Prepare a fetch initialization file using the form data
    const init = {
        method: 'POST',
        body: formData
    };
    //
    //5. Use the initialization object to send the file to the server
    const response = await fetch('/schema/v/code/export.php?post_file=true', init);
    //
    //await for the output which has the following structure
    //{ok, result, html}
    //ok
    const output = await response.json();
    //
    return output;
}
//
//The ifetch function is used for executing static methods on php class
export async function ifetch<
    //
    //Define a type for... 
    //...the collection of classes in the library namespace.
    classes extends typeof library,
    // 
    //...all the class names that index the classes.
    class_name extends Extract<keyof classes, string>,
    // 
    //...a class in the library name spaces
    $class extends classes[class_name],
    // 
    //...a static method name of class in the library namespace 
    method_name extends Extract<keyof $class, string>,
    // 
    //...a static method of a $class in the library namespace
    method extends Exclude<
        Extract<$class[method_name], (...args: any) => any>,
        "prototype"
    >,
    // 
    //...input parameters of a method of a class in the library namespace 
    $parameters extends Parameters<method>,
    // 
    //...a return value of a method of a class in the library namespace 
    $return extends ReturnType<method>

>(
    //
    //The class of the php object to use.
    class_name: class_name,
    //
    //The static method name to execute on the class. 
    method_name: method_name,
    //
    //The method parameters
    margs: $parameters
): Promise<$return> {
    //
    //Call the non parametric form of exec, without any constructor 
    //arguments
    return await exec_nonparam(class_name,method_name,margs);
}

//
//This is the non-parametric version of exec useful for calling both the static
// and object of the given php class
export async function exec_nonparam(
    //
    //This is the name of the php class to create
    class_name: string,
    //
    //The method to execute on the php class
    method_name:string,
    //
    //The arguements of the method
    margs:Array<any>,
    //
    //If defined, this parameter represents the constructor arguements for the 
    //php class. It is undefined for static methods.
    cargs:Array<any>|null=null
 ): Promise<any>{
    //
    //Prepare to collect the data to send to the server
    const formdata = new FormData();
    //
    //Add the application URL from the schema class
    formdata.append("url", schema.schema.app_url);
    //
    //Add to the form, the class to create objects on the server
    formdata.append('class', class_name);
    //
    //Add the class constructor arguments if they are defined
    if (cargs === null ){
        //
        //The method on the php class is static
        formdata.append('is_static', 'true');
    }
    else{
        //
        //The method on the php class is an object method
        formdata.append('cargs', JSON.stringify(cargs));
    }
    //
    //Add the method to execute on the class
    formdata.append('method', method_name);
    //
    //Add the method parameters 
    formdata.append('margs', JSON.stringify(margs));
    //
    //Prepare  to fetch using a post
    const init = {
        method: 'post',
        body: formdata
    };
    //
    //Fetch and wait for the response, using the (shared) export file
    const response = await fetch('/schema/v/code/export.php', init);
    //
    //Get the text from the response. This can never fail
    const text = await response.text();
    //
    //The output is expected to be a json string that has the following 
    //pattern: {ok:boolean, result:any, html:string}. If ok, the 
    //result is that of executing the requested php method; otherise it
    //is an error message. htm is any buffered warnings.
    let output:{ok:boolean, result:any, html:string}; 
    //
    //The json might fail (for some reason)
    try {
        //Try to convert the text into json
        output = JSON.parse(text);
    }
    //
    //Invalid json;this must be a structural error that needs special attention
    catch (ex) {
        // 
        //Compile a usefull error message
        const msg = 
        `Error trapping failed???. <br/> Message: "${(<Error> ex).message}".<br/>Text = "${text}"`;
        //
        throw new schema.mutall_error(msg);
    }
    //
    //The json is valid.
    // 
    //Test if the requested method ran successfully 
    if(output.ok) return output.result;
    //
    //The method failed. Report the method specific errors. The result
    //must be an error message string
    const msg= <string>output.result;
    // 
    //Report the error. 
    throw new schema.mutall_error(msg);
}

