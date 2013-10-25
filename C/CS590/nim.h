#ifndef NIM_H
#define NIM_H

#include <windows.h>

#define MAX_PILES 10
#define MAX_TOKENS 10
#define MAX_SQUARE 72  // pixel size of maximum square for display

class Nim {
    int initialPileCount;  // number of rows in the specified game type
    int gamePileCount;     // number of rows in game being played
    int initialPiles[MAX_PILES]; // token counts for each row in game type
    int gamePiles[MAX_PILES];    // token counts for each row in game played
    int maxMove;      // maximum number of tokens on one move
    enum {LastLoses=1,LastWins=0};
    int winPosition; // one of Last_loses or Last_wins
    enum {YouFirst=1,MeFirst=0};
    int gameStart; // one of YouFirst or MeFirst
    void myMove(int*,int*); // function to compute computer's move
    void randomMove(int*,int*); // when in losing state make a random move
  public:
    Nim(); // default constructor
    int toggleStart() { gameStart^=1; return(gameStart); }
    int toggleWinner() { winPosition^=1; return(winPosition); }
    void setParameters(int*,int,int);
    // sets initialPiles,initialPileCount & maxMove
    void getParameters(int*,int*,int*);
    // retrieves initialPiles,initialPileCount &  maxMove
    int playerFirstMove() { return(gameStart); } // Boolean
    void concede() { gamePileCount=0; } // set game status to over
    int gameOver() { return(!gamePileCount); } // Boolean

    friend class NimView;
};

class NimView {
    Nim& theGame; // the game for this view
    RECT viewport;// all tokens displayed in this rectangle
    int sqWidth;  // width of a square enclosing a token
    int delta;  // tokens displayed at offset delta in  above square 
    int circleRadius; // radius of circle representing a token
    HBRUSH hGreen; // windows "brushes"
    HBRUSH hRed;
    HBRUSH hYellow;
    enum {White,Green,Yellow,Red};
    int tokenColors[MAX_PILES][MAX_TOKENS];
    /* array of token colors for all possible tokens in the game
       White - not present
       Green - present
       Yellow - selected by player
       Red - selected by me (computer)
    */
    int currentPile,myMovePile;
    // currentPile is row selected by player; myMovePile is row selected
    // by me
  public:
    NimView(Nim&); // constructor
    void resetGame(); // starts a new game
    void setViewport(int,int);
    void showCircle(HWND,int,int);
    void showGame(HWND);
    void showPlayerSelection(HWND hwnd,POINT p);
    int showPlayerMove(HWND);
    int showMyMove(HWND);
    void deleteBrushes();

};

#endif
