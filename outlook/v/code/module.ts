//
//This is the questionnaire interface that drives collecting information from the level 2 registration form
//in either a label or tabular layout
interface questionnaire {

}
//
//This is the default crontab interface which contains the specifications that
//allow the automated scheduling of tasks.
export interface crontab {
    //

}
//
//The double entry interface allows capturing transaction data, which is later used
//to populate the different accounts.i.e., the office account
interface double_entry {

}
//
//The messages interface that allows sending messages from one user to another.
interface message {

}
//
//The aim of this class is to support scheduling of tasks similar to how "LINUX'S
//CRONTAB" command schedules tasks to automatically occur.
export class schedule implements crontab {
    constructor() {

    }
}
//
//This class supports the registrar module developed to support level 2 investigation
//
class writer implements questionnaire {
    //
    //The class writer implements the save method implements the questionnaire interface
    async save(): Promise<void> {
        return
    }
}
//
//The accounting class that captures transaction data in a double entry format
//which then proceeds to split into the refined data as per the DEALER model. Once
//done the transaction it is labelled as a debit or credit within an application.   
class accouting implements double_entry {

}
//
//The messenger class supports sending of messages from one user to another.
export default class messenger implements message {

}