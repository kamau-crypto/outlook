import {popup,user,view} from "./outlook.js";
//
//Resolve the schema classes, viz.:database, columns, mutall e.t.c. 
import * as schema from "../../../schema/v/code/schema.js"
//
//Resolve the server method for backend communication
import * as server from "../../../schema/v/code/server.js"
//
//Define data types for all our element's ids/name in the login page.
//
//Group all the available providers and defines a data type for them. This will
//allow for handling(Hiding and Showing) the credentials.
type provider_id = 'google' | 'facebook' | 'outlook';
//
//types of operations for accesing the application service 
type operation_id = "login" | "register";
   
//
//This is a page used for authenticating users so 
//that they can be allowed to access the application 
//services. The popup takes in a provider and returns a user
export class page extends popup<user> {
    // 
    //The authentication provider for this page 
    public provider?: provider;
    //
    constructor(url:string ) {
        //
        //Use the config file to get the login url
        //super(app.current.config!.login);
        super(url)
    }
    //
    //The show panels method that paints the desired output
    async show_panels():Promise<void>{
        return
    }
    
    //Return the logged in user
    async get_result(){
        //
        //Check whether the input are valid or not
        //
        //Get the provider
        this.provider = this.retrieve();
        //
        //Authenticate to get the user
        const User:user = await this.provider.authenticate();
        //
        //Compile the login response
        return User;
    }
    
    //
    //Retrieves a provider
    retrieve(): provider {
        //
        //Retrieve the checked provider id
        let values= this.get_input_choices('provider_id');
        //
        //Check the values for validity
        if (values.length !== 1){
            throw new schema.mutall_error(`Please select one provider`);
        }
        const provider_id = <provider_id>values[0];
        // 
        //Retrieve the checked operation id 
        values= this.get_input_choices('operation_id');
        //
        //Check the values for validity
        if (values.length !== 1){
            throw new schema.mutall_error(`Please select one Operation`);
        }
        const operation_id = <operation_id>values[0];
        //
        //1. Define the provider
        let Provider: provider;
        //
        switch (provider_id) {
            case "outlook":
                //
                //Retrieve the credentials
                const email =
                    (<HTMLInputElement>this.get_element('email')).value;
                //     
                const password =
                    (<HTMLInputElement>this.get_element('password')).value;
                //  
                Provider = new outlook(email, password,operation_id);
                break;
            default:
                throw new schema.mutall_error("The selected provider is not yet developed");
        }
        //
        return Provider;
    }
      
    //Check if we have the correct data before we close, i.e., if the
    //provider is outlook. See if there are inputs in 
    //the input fields.
    async check():Promise<boolean> {
        //
        //1. Proceed only if the provider is outlook.
        if (!(this.provider instanceof outlook)) return true;
        //
        //Define a fuction for identifiyng and notifying empty values
        const is_valid = (id: string): boolean => {
            //
            const elem = <HTMLInputElement>this.get_element(id);
            //
            const is_empty = ((elem.value === null) || elem.value.length === 0);
            //
            //Notify (on the login page) if empty
            if (is_empty) {
                //
                //Get the notification tag; its next to the id
                const notify = <HTMLElement>elem.nextElementSibling;
                notify.textContent = `Empty ${id} is not allowed;`
            }
            return !is_empty;
        }
        //
        //2. Check if e-mail is empty, then flag it as an error if it is empty.
        const email_is_valid: boolean = is_valid('email');
        //
        //3. Check if password is empty, then flag it as an error if it is 
        //empty.
        const password_is_valid: boolean = is_valid('password');
        //
        //Return true if both the email and password are valid 
        return email_is_valid && password_is_valid;
    }
}

  //
  //This class represents authentication service providers
  // eg. google,facebook,github
export abstract class provider {
      // 
      //The request to the provider 
    public operation_id: operation_id;
      //
      //Every service provider is identified by this name
      //e.g google,facebook.
      public name: string;
      //
      //Initialize the provider using the name. 
      constructor(name: string, operation:operation_id) {
          this.name = name;
          this.operation_id = operation;
      }
      //
      //Allows users to sign in using this provider. 
      //Every provider must supply its own version of 
      //signing in hence abstract.
      abstract authenticate(): Promise<user>;
  }

  // This class represents the authentication services provided by google.
  class google {

      constructor(operation:operation_id) {
          //super('google',operation);
      }
      //
      //This method allows users to signin using their google 
      //account;it is borrowed from firebase.
    //   async authenticate():Promise<user> {
    //       //Google Authentication.
    //       //Provider required
    //       var provider = new firebase.auth.GoogleAuthProvider();
    //       //
    //       //Google login with redirect.
    //       await firebase.auth().signInWithRedirect(provider);
    //       //
    //       //
    //       const uuid = await firebase.auth().getRedirectResult();
    //       //
    //       //Create an applicatioon user
    //       const User:user = new user(uuid.user!.email!);
    //       //
    //       //Extract the provider details that we require for our user 
    //       //identification
    //       User.first_name = uuid.user!.displayName,
    //       User.full_name = uuid.user!.displayName,
    //       User.picture = uuid.user!.photoURL;
          
    //       //Return the new user
    //       return User;
    //   }
  }
//
//Represents our custom login provided firebase
class outlook extends provider {
    //
    //
    public email: string;
    public password: string;

    constructor(email: string, password: string,operation:operation_id) {
        super('outlook',operation);
        this.email = email;
        this.password = password;
    }
    //
    //This is our custom made signing method using php hashing. 
    async authenticate(): Promise<user> {
        //
        //Check whether the user is registering or loging in;
        //if registering then create an account 
        if (this.operation_id === "register"){
            //
            //Registration 
            //
            //Create the user account
            await server.exec(
              "database",
              ["mutall_users"], 
              "register",
                [this.email, this.password]);
        } else {
            //
            //LOGIN
            //Authenticate the user using the given email and password 
            const ok=await server.exec(
              "database",
              ["mutall_users"], 
              "authenticate",
                [this.email, this.password]);
            //
            //If the login is not successful throw an exception
            if (!ok) throw new schema.mutall_error("Invalid login credentials");
        }
        //
        return new user(this.email);
    }    
      
}
  // 
  //Solomon was and lawrence have to develop this class
  //because facebook requires special setup.
  class facebook {
      // 
      // 
      constructor(operation:operation_id) {
          // 
          // 
          //super('facebook',operation);
      }
      //
      //This method allows users to signin using their google 
      //account;it is borrowed from firebase.
    //   async authenticate():Promise<user> {
    //     //Google Authentication.
    //     //Provider required
    //     var provider = new firebase.auth.FacebookAuthProvider();
    //     //
    //     //Google login with redirect.
    //     await firebase.auth().signInWithRedirect(provider);
    //     //
    //     //
    //     const uuid = await firebase.auth().getRedirectResult();
    //     uuid.user!.email 
    //     //
    //     //Create an applicatioon user
    //     const User:user = new user(uuid.user!.email);
    //     //
    //     //Extract the provider details that we require for our user 
    //     //identification
    //     User.first_name = uuid.additionalUserInfo!.username;
    //     User.full_name = uuid.user!.displayName,
    //     User.picture = uuid.user!.photoURL;
        
    //     //Return the new user
    //     return User;
    // }
  }
  
