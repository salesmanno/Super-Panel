import html from "./_current.html?raw";
export function Current(){return <iframe title="Dagens innsjekking, hentet fra modulkoden" srcDoc={html} style={{width:"100%",height:"100vh",border:0,display:"block"}} sandbox="allow-scripts"/>}
