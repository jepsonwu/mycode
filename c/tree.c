#include <stdio.h>
#include <stdlib.h>
typedef struct node *position;
typedef int elementTp;//这样的好处是，如果要换二叉树存储的类型，在这里换以下就可以了

//二叉树节点结构
struct node {
	int  element;
	position p_index;
	position  l_index;
	position r_index;
	//没有指定父节点指针
};

typedef struct node *tree;

tree insert_node(tree t,int num);//这里不知道怎么用指针实现
void insert_node_nempty(tree t,position np);
position find_node(tree t,int num);
position min_node(tree t);
position max_node(tree t);
int delete_node(position np);
static int is_leaf(position np);
static int is_root(position np);
static int delete_leaf(position np);

//二叉搜索树
//这里没有考虑到重复的问题 todo

void main(){
	int arr[10]={3,9,6,8,5,4,2,1,7,55};
	int i;

	tree t=NULL;
	//新增节点
	for (i = 0; i < 10; ++i)
	{
		t=insert_node(t,arr[i]);
	}
	
	//查找节点
	position np;
	np=find_node(t,8);
	//printf("%p:%d\n", np,np->element);

	//最小节点
	// np=min_node(t);
	// printf("%p:%d\n", np,np->element);
	
	//最大节点
	// np=max_node(t);
	// printf("%p:%d\n", np,np->element);

	//删除节点
	int element;
	element=delete_node(np);
	printf("%d\n", element);

	//反转二叉树
	
	//打印二叉树所有节点
	
	//打印二叉树节点总数
}

/**
 * 插入节点
 * @param  t   [description]
 * @param  num [description]
 * @return     [description]
 */
tree insert_node(tree t,int num){
	position np;
	np=(position) malloc(sizeof(position));

	np->element=num;
	np->p_index=NULL;
	np->l_index=NULL;
	np->r_index=NULL;

	if(t==NULL){
		t=np;
	}else{
		insert_node_nempty(t,np);
	}

	return t;
}

void insert_node_nempty(tree t,position np){
	//小于当前节点放左边
	if(np->element<=t->element){
		if(t->l_index==NULL){
			np->p_index=t;
			t->l_index=np;
			return;
		}else{
			insert_node_nempty(t->l_index,np);
		}
	}else{//大于当前节点，则为右子节点
		if(t->r_index==NULL){
			np->p_index=t;
			t->r_index=np;
			return;
		}else{
			insert_node_nempty(t->r_index,np);
		}
	}
}

/**
 * 查找节点
 * @param  t   [description]
 * @param  num [description]
 * @return     [description]
 */
position find_node(tree t,int num){
	position np;
	np=t;

	while(np!=NULL){
		if(np->element==num){
			return np;
		}

		if(np->element<num){
			np=np->r_index;
		}else{
			np=np->l_index;
		}
	}

	return NULL;
}

/**
 * 最小节点
 * @param  t [description]
 * @return   [description]
 */
position min_node(tree t){
	position np;
	np=t;

	while(np->l_index!=NULL){
		np=np->l_index;
	}

	return np;
}

/**
 * 最大节点
 * @param  t [description]
 * @return   [description]
 */
position max_node(tree t){
	position np;
	np=t;

	while(np->r_index!=NULL){
		np=np->r_index;
	}

	return np;
}

/**
 * 删除节点
 * @param t  [description]
 * @param np [description]
 */
int delete_node(position np){
	position replace;
	int element;

	if(is_leaf(np)){//如果是叶节点，删除即可
	        return delete_leaf(np);

	}else{//如果不是叶节点，把值替换以下,指针方面不需要任何处理,第归下去
		//找到左边最大的节点，或者右边最小的节点
		replace=(np->l_index!=NULL)?max_node(np->l_index):min_node(np->r_index);
		element=np->element;
		np->element=delete_node(replace);
		return element;
	}
}

/**
 * 删除叶节点
 * @param np [description]
 */
static int delete_leaf(position np){
	position parent;
	parent=np->p_index;
	int element=np->element;

	if(!is_root(np)){
		if(parent->l_index=np){
			parent->l_index=NULL;
		}else{
			parent->r_index=NULL;
		}
	}

	free(np);

	return element;
}

/**
 * 是不是叶节点
 * @param  np [description]
 * @return    [description]
 */
static int is_leaf(position np){
	return (np->l_index==NULL&&np->r_index==NULL);
}

/**
 * 是否是根节点
 * @param  np [description]
 * @return    [description]
 */
static int is_root(position np){
	return (np->p_index==NULL);
}