#include "nim.h"

#include <stdlib.h>



Nim::Nim() : initialPileCount(3),maxMove(3),winPosition(LastLoses),

             gameStart(YouFirst)

{ initialPiles[0]=5;

  initialPiles[1]=4;

  initialPiles[2]=3;

}



void Nim::setParameters(int *a,int n,int m)

/* Merely copies n into initialPileCount and array a into initialPiles.

   The caller MUST ensure these parameters are valid.

*/

{  initialPileCount=n;

   maxMove=m;

   for (int i=0;i<n;initialPiles[i]=a[i],i++)

     ;

}



void Nim::getParameters(int *buff,int *count,int *moves)

/*  Copies initialPileCount to *count and initialPiles to buff.  Caller

    ensures that buff is large enough to hold a copy of initialPiles.

*/

{  *count=initialPileCount;

   *moves=maxMove;

   for (int i=0;i<initialPileCount;buff[i]=initialPiles[i],i++)

     ;

}



void Nim::randomMove(int *row,int *count)

/*  Generates a "random" move by randomly selecting a row, going to the

    next non-zero row and then choosing a random number of tokens from

    this non-zero row.

*/

{ int r=rand()%initialPileCount;

  while (gamePiles[r]==0) {

    r++;

    if (r>=initialPileCount)

      r=0;

  }

  *row=r;

  int mod=(gamePiles[r]<=maxMove) ? gamePiles[r] : maxMove;

  *count=rand()%mod+1;

  gamePiles[r]-=*count;

  if (!gamePiles[r])

    gamePileCount--;

}





void Nim::myMove(int *row,int *count)

/*  The heart of the program.  This function determines if the current

    state is a winning state (for the computer).  If not, randomMove is

    invoked and just return.  Otherwise, the optimal strategy is employed

    to ensure that the computer will ultimately win.

*/
 /*{ randomMove(row,count);
	gamePiles[*row]-=*count;
	if(gamePiles[*row])
		gamePileCount--;
 } */



{	int M = maxMove + 1; int r = 0; int q = 0; int ones = 0; int positives = 0;
	for(int i = 0; gamePiles[i] <= gamePileCount;i++) {
		r+=i % M; q+=i / M;
		if(r == 1)
			ones++;
		if(r > 0)
			positives++;
	}
	if(positives == ones) {					// Case 1 
		if(winPosition) {						// LastLoses = 1
			if(!(ones % 2)) {					// ones is even 
				int i = 0; int r = 0; int is = 0;
				while(gamePiles[i] <= gamePileCount) {
					if(r % gamePiles[i] == 1) {
						*row = r; *count = 1; is = 1;
					}
					i++; r = i % M;
				}
				if(!is) { 						// row r == 1 did not exist 
					int i = 0; int q = 0;
					while(gamePiles[i] <= gamePileCount) {
						if(q % gamePiles[i] != 0) {
							*row = q; *count = maxMove; // ?? 
						}
						i++; q = i / M;
					}
					randomMove(row,count);
					gamePiles[*row]-=*count;
					if(gamePiles[*row])
						gamePileCount--;
				}
			}
		}
		if(ones % 2) {							// ones is odd, LastLoses = 0 
			int i = 0; int r = 0; int is = 0;
			while(gamePiles[i] <= gamePileCount) {
				if(r % gamePiles[i] == 1) {
					*row = r; *count = 1; is = 1;
				}
				i++; r = i % M;
			}
			if(!is) {							// row r ==1 did not exist 
				int i = 0; int q = 0;
				while(gamePiles[i] <= gamePileCount) {
					if(q % gamePiles[i] != 0) {
						*row = q; *count = maxMove; // ?? 
					}
				i++; q = i / M;
				}
			}
			gamePiles[*row]-=*count;
			if(gamePiles[*row])
				gamePileCount--;
		}
		randomMove(row,count);
		gamePiles[*row]-=*count;
		if(gamePiles[*row])
			gamePileCount--;
	}  	
	if(positives == (ones +1)) {			// Case 2 
		if(winPosition) {						// LastLoses = 1 
			if(!(ones % 2)) {					// ones is even 
				int i = 0; int r = 0;
				while(gamePiles[i] <= gamePileCount) {
					if(r % gamePiles[i] > 1) {
						*row = r; *count = maxMove - 1;
					}
				i++; r = i % M;
				}
			}
			int i = 0; int r = 0;
			while(gamePiles[i] <= gamePileCount) {  // ones is odd 
				if(r % gamePiles[i] > 1) {
					*row = r; *count = maxMove;
				}
			i++; r = i % M;
			}
		}
		if(ones % 2) {							// LastLoses = 0, ones is odd
			int i = 0; int r = 0;
				while(gamePiles[i] <= gamePileCount) {
					if(r % gamePiles[i] > 1) {
						*row = r; *count = maxMove - 1;
					}
				i++; r = i % M;
				}
		}
		int i = 0; int r = 0;
		while(gamePiles[i] <= gamePileCount) {  // ones is even 
			if(r % gamePiles[i] > 1) {
					*row = r; *count = maxMove;
			}
			i++; r = i % M;
		}
		gamePiles[*row]-=*count;
		if(gamePiles[*row])
			gamePileCount--;
	}
		// Case 3 
	for(int r_3 = 0, i_3 = 0, c = 0;gamePiles[i_3] <= gamePileCount;i_3++) {
		r_3+= i_3 % M;
		c^=r_3;
	}
	if(!c)							// c is 0, losing state, random move 
		randomMove(row,count);
	int r_4 = 0; int i_4 = 0;
	while(gamePiles[i_4] <= gamePileCount) {
		if((r_4 > 0) && (r_4^c < r_4)) {
			*row = r_4; *count = (r_4 - r_4^c);
		}
		i_4++; r_4 = i_4 % M;
	}
	gamePiles[*row]-=*count;
	if(gamePiles[*row])
   	gamePileCount--;
}



NimView::NimView(Nim& x) : theGame(x)

// Just set theGame, create the brushes and reset the game.

{ hRed=CreateSolidBrush(RGB(255,0,0));

  hGreen=CreateSolidBrush(RGB(0,255,0));

  hYellow=CreateSolidBrush(RGB(255,255,0));

  resetGame();

}



void NimView::resetGame()

/*  Copies the initial game info into game info, sets currentPile and

    myMovePile to -1 indicating no selected piles yet and initializes the

    tokenColors.

*/

{ currentPile=-1; // no selections yet

  myMovePile=-1;  // no computer move pile yet

  int i,j;

  theGame.gamePileCount=theGame.initialPileCount;

  for (i=0;i<theGame.initialPileCount;i++)

    theGame.gamePiles[i]=theGame.initialPiles[i];

  for (i=0;i<theGame.initialPileCount;i++) {

    for (j=0;j<theGame.initialPiles[i];j++)

      tokenColors[i][j]=Green;

    for (;j<MAX_TOKENS;j++)

      tokenColors[i][j]=White;

  }

  for (;i<MAX_PILES;i++)

    for (j=0;j<MAX_TOKENS;j++)

      tokenColors[i][j]=White;

}



void NimView::setViewport(int width,int height)

/* Computes the viewport; i.e., the rectangle where the game is actually

   displayed.  Also the sqWidth of the bounding box of a circular token

   is computed.  Finally delta (the offset in the bounding box of the

   circle) and circleRadius are computed.

*/

{  int max_y=theGame.initialPileCount,max_x,i;

   for (max_x=0,i=0;i<theGame.initialPileCount;i++)

     if (max_x<theGame.initialPiles[i])

       max_x=theGame.initialPiles[i];

   int vert_count=height/max_y,horiz_count=width/max_x;

   sqWidth=(vert_count<=horiz_count) ? vert_count : horiz_count;

   if (sqWidth>MAX_SQUARE)

     sqWidth=MAX_SQUARE;

   delta=sqWidth/6;

   circleRadius=delta<<1;

   int view_width=max_x*sqWidth;

   int view_height=max_y*sqWidth;

   viewport.left=(width-view_width)>>1;

   viewport.right=viewport.left+view_width;

   viewport.top=(height-view_height)>>1;

   viewport.bottom=viewport.top+view_height;

}



void NimView::showCircle(HWND hwnd,int i,int j)

/* Draws a circle at the square corresponding to row position i and column

   position j of the game.  Circle is drawn in color specified in

   tokenColors[i][j] (hence caller must ensure this array is up to date).

*/

{  int y=viewport.top+i*sqWidth;

   int horiz_offset=

       (viewport.right-viewport.left-theGame.initialPiles[i]*sqWidth)>>1;

   int x=viewport.left+horiz_offset+j*sqWidth;

   HDC hdc;

   hdc=GetDC(hwnd);

   if (tokenColors[i][j] != White) {

     HBRUSH hBrush;

     switch (tokenColors[i][j]) {

       case Green:

         hBrush=hGreen; break;

       case Yellow:

         hBrush=hYellow; break;

       case Red:

         hBrush=hRed; break;

       default: hBrush=hYellow;

     }

     SelectObject(hdc,hBrush);

     Ellipse(hdc,x+delta,y+delta,x+sqWidth-delta,y+sqWidth-delta);

   }

   else

     PatBlt(hdc,x,y,sqWidth,sqWidth,WHITENESS);

   ReleaseDC(hwnd,hdc);

}



void NimView::showGame(HWND hwnd)

/*  Shows all the tokens in the appropriate colors for the current state

    of the game.

*/

{ int i,j;

  for (i=0;i<theGame.initialPileCount;i++)

    for (j=0;j<theGame.initialPiles[i];j++)

      showCircle(hwnd,i,j);

}



void NimView::showPlayerSelection(HWND hwnd,POINT p)

/*  Computes the token position corresponding to p and updates tokenColors

    currentPile and the screen.

*/

{ if (theGame.gamePileCount==0)

    return;  // game over

  // First remove my red tokens if present

  if (myMovePile>=0) {

    for (int l=0;l<theGame.initialPiles[myMovePile];l++)

      if (tokenColors[myMovePile][l]==Red) {

        tokenColors[myMovePile][l]=White;

        showCircle(hwnd,myMovePile,l);

      }

    myMovePile=-1;

  }

  int i=(p.y-viewport.top)/sqWidth;

  if (i<0 || i>=theGame.initialPileCount || !theGame.gamePiles[i])

    return;

  int horiz_offset=(viewport.right-viewport.left-sqWidth*theGame.initialPiles[i])>>1;

  int j=(p.x-viewport.left-horiz_offset)/sqWidth;

  if (j<0 || j>=theGame.initialPiles[i])

    return;

  int centre_y=viewport.top+sqWidth*i+delta+circleRadius;

  int centre_x=viewport.left+horiz_offset+sqWidth*j+delta+circleRadius;

  int dist_x=p.x-centre_x,dist_y=p.y-centre_y;

  int distance=dist_x*dist_x+dist_y*dist_y;

  if (distance>circleRadius*circleRadius)

    return;

  if (tokenColors[i][j]==White)

    return;

  if (currentPile>=0 && currentPile != i)

    for (int l=0;l<theGame.initialPiles[currentPile];l++)

      if (tokenColors[currentPile][l]==Yellow) {

        tokenColors[currentPile][l]=Green;

        showCircle(hwnd,currentPile,l);

      }

  currentPile=i;

  if (tokenColors[i][j]==Yellow)

    tokenColors[i][j]=Green;

  else {

    int marked=0;

    for (int l=0;l<theGame.initialPiles[i];l++)

      if (tokenColors[i][l]==Yellow)

        marked++;

    if (marked==theGame.maxMove)

      return;

    tokenColors[i][j]=Yellow;

  }

  showCircle(hwnd,i,j);

}



int NimView::showPlayerMove(HWND hwnd)

/*  This shows the player move and the computer's response.  Return is 0

    if the game is over upon entry, no selection of tokens by player or

    if the game continues after player move and computer move.  Return of

    1 indicates game is over with player winning; 2 means computer won.

*/

{ if (!theGame.gamePileCount || currentPile<0)

    return(0);

  for (int j=0,count=0;j<theGame.initialPiles[currentPile];j++)

    if (tokenColors[currentPile][j]==Yellow) {

      count++;

      tokenColors[currentPile][j]=White;

      showCircle(hwnd,currentPile,j);

    }

  if (!count)

    return(0);

  theGame.gamePiles[currentPile]-=count;

  if (!theGame.gamePiles[currentPile])

    theGame.gamePileCount--;

  if (theGame.gamePileCount)

    return(showMyMove(hwnd));

  return((theGame.winPosition) ? 2 : 1);

}



int NimView::showMyMove(HWND hwnd)

/*  This function invokes theGame.myMove, sets myMovePile to the resulting

    row, updates array tokenColors according to the results of myMove, and 

    displays these tokens in red. If the game continues (gamePileCount != 0),

    return is 0, otherwise, return is 2 for computer won and 1 for player won.

*/
{	int row, count;
	theGame.myMove(&row, &count);
	myMovePile=row;
	for(theGame.initialPiles[row]-1;count;count--) {
		if(tokenColors[myMovePile][count]==Green)   	
			tokenColors[myMovePile][count]=Red;
		showCircle(hwnd, myMovePile, count);
		
	}
	if(theGame.gamePileCount!=0)
		return(0);
	else
		return((theGame.winPosition) ? 2 : 1);
}



      

void NimView::deleteBrushes()

/*  Just deletes the windows brushes created by the NimView constructor.

*/

{ DeleteObject(hGreen);

  DeleteObject(hRed);

  DeleteObject(hYellow);

}

