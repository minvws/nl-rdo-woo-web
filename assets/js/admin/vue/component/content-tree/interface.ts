export interface ContentTree {
  title: string;
  intro: string;
  children?: ContentTreeChildren[];
  outro: string;
}

export interface ContentTreeChildren {
  title: string;
  body: string;
  children?: ContentTreeChildren[];
}
