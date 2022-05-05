// class svg extends outlook.baby<void>{
//     //
//     //
//     //
//     constructor(
//         // 
//         //This popup parent page.
//         mother: outlook.page,
//         //
//         //The html file to use
//         filename: string
//         //
//         //The primary key columns,i,e the first records in the eHTMLTableCellElement
//     ) {
//         // 
//         //The general html is a simple page designed to support advertising as 
//         //the user interacts with this application.
//         super(mother, filename);
//         //
//     }
//     async check(): Promise<boolean> { return true; }
//     async get_result(): Promise<void> { }
//     //
//     //
//     async show_panels(): Promise<void> {
//         //
//         //Get the svg element
//         const svg: SVGElement = document.querySelector("svg")!;
//         //
//         //Create the svg point
//         const toSVG = (svg: SVGSVGElement, x: SVGPoint, y: SVGPoint) => {
//             //
//             const p: SVGPoint = new DOMPoint();
//             //
//             //Get the mouse coordinates to transform to svg coordinates.
//             const point = p.matrixTransform(svg.getScreenCTM()!.inverse());
//             //
//             return point;
//         }
//         svg.onclick = (e: MouseEvent) => {
//             //
//             //Get the screen coordinates
//             const coord = toSVG(e.target, e.clientX, e.clientY)=> {
//                 //
//                 //Obtain the points of the circles
//                 const c1x: number = coord.x;
//                 const c1y: number = coord.y;
//                 const c2x: number = 450;
//                 const c2y: number = 75;
//                 const radius: number = 75;
//                 //
//                 //Calculate the angle of inclination of the line
//                 const incX: number = (c2x - c1x);
//                 const incY: number = (c1y - c2y);
//                 const theta: number = Math.atan(incY / incX);
//                 //
//                 //The distance from the center of the circle to the point the line joins the circle
//                 const dx: number = Math.cos(theta) * radius;
//                 const dy: number = Math.sin(theta) * dx;
//                 //
//                 //Get the line that connects both of the circles
//                 const line: HTMLElement = this.get_element("#line");
//                 //
//                 //Isolate the point of intersection of the line with the circles
//                 //Circle 1,The point x1
//                 const px1 = parseFloat(line.getAttributeNS(null, 'x1')!);
//                 svg.setAttributeNS(null, 'x1', c1x + dx);
//                 //
//                 //Circle1, The point y1
//                 const py1: number = parseFloat(line.getAttributeNS(null, 'y1')!);
//                 svg.setAttributeNS(null, 'y1', c1y - dy);
//                 //
//                 //Circle2, the point x2
//                 const px2 = parseFloat(line.getAttributeNS(null, 'x2')!);
//                 svg.setAttributeNS(null, 'x2', c2x - dx);
//                 //
//                 //Circle 2, the point y2
//                 const py2 = parseFloat(line.getAttributeNS(null, 'y2')!);
//                 svg.setAttributeNS(null, 'y2', c2y + dy);
//                 //
//                 return px1, py1, px2, py2;
//             }
//         }

//         //
//         //Once those points are con

//     }
// }