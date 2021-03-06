# LFS Vectors

## Introduction
Vectors in general tend to be rather confusing until they are explained. There are three different types you will see. Position Vectors, Velocity Vectors, and Angle Vectors. While in most basic cases you will probably only use one of these at a time, it doesn't hurt to know what the other two parameters are there for. Here is an example of the OutSimPack packet to show you what it looks like:
```c++
struct OutSimPack
{
	unsigned	Time;	// time in milliseconds (to check order)

	Vector	AngVel;		// 3 floats, angular velocity vector
	float	Heading;	// anticlockwise from above (Z)
	float	Pitch;		// anticlockwise from right (X)
	float	Roll;		// anticlockwise from front (Y)
	Vector	Accel;		// 3 floats X, Y, Z
	Vector	Vel;		// 3 floats X, Y, Z
	Vec		Pos;		// 3 ints   X, Y, Z (1m = 65536)

	int		ID;			// optional - only if OutSim ID is specified
};
```

**Angular Velocity (AngVel)**: The first set of Vectors in this packet, declared as AngVel. This tells us how fast the object is turning in the form of degress a second.

**Angles Vector**: The second set of Vectors in this packet, declared as Heading, Pitch & Roll. This specifies the rotation of an object. This vector would follow the format that modifies the angles at which the object is looking. 'Pitch' is the up & down angles, 'Heading' is the left & right, and 'Roll' is the spin along the up/down/left/right vector.

**Acceleration (Accel) Vector**: The middle set of Vectors in this packet, declared as Accel. This vector tells us how obrupt an object is changing it's direction in meters per second.

**Velocity (Vel) Vector**: The penultimate set of Vectors in the packet is the Velocity Vector, declared as Vel in the packet. This vector tells the game where the object will be on the next frame. So on the next game frame, the Position Vector of this object will be the sum of the Position Vector and the Velocity Vector from our current frame.

**Position (Pos) Vector**: The last set of Vectors this packet, declared as Pos. This simply tells us the entities absolute position in the world. The position vector could look something like {983040, 1966080, 983040} and this would mean the entity is 15 meters along the X axis, 30 meters along the Y axis, and 15 meters along the Z axis.